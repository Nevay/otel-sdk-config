<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Common\SelfDiagnosticsContext;
use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Env\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\DebugEnvReader;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvComponentLoaderRegistry;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvResolver;
use Nevay\OTelSDK\Configuration\Internal\LoggerHandler;
use Nevay\OTelSDK\Configuration\SelfDiagnostics\DisableSelfDiagnosticsConfigurator;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\NoopLoggerProvider;
use Nevay\OTelSDK\Metrics\ExemplarFilter;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\NoopMeterProvider;
use Nevay\OTelSDK\Trace\NoopTracerProvider;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\ConfigurationRegistry;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\InstrumentationConfiguration;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use function array_unique;

final class Env {

    public static function load(
        ?EnvReader $envReader = null,
    ): ConfigurationResult {
        $envReader ??= new EnvSourceReader([
            new ServerEnvSource(),
            new PhpIniEnvSource(),
        ]);

        $logLevel = (new EnvResolver($envReader))->string('OTEL_LOG_LEVEL') ?? 'info';

        $logger = new Logger('otel');
        $logger->pushHandler(new ErrorLogHandler(level: $logLevel));
        $logger->debug('Initializing OTelSDK from env');

        $registry = new EnvComponentLoaderRegistry();
        foreach (ServiceLoader::load(EnvComponentLoader::class) as $loader) {
            $registry->register($loader);
        }

        $env = new EnvResolver(new DebugEnvReader($envReader, $logger), $logger);
        $context = new Context(logger: $logger);

        if ($env->bool('OTEL_SDK_DISABLED') ?? false) {
            $propagator = self::propagator($env, $registry, $context);
            $configProperties = self::configProperties($env, $registry, $context);

            $logger->debug('Initialized OTelSDK from env', ['disabled' => true]);

            return new ConfigurationResult(
                $propagator,
                new NoopTracerProvider(),
                new NoopMeterProvider(),
                new NoopLoggerProvider(),
                $configProperties,
                $logger,
            );
        }

        $tracerProviderBuilder = new TracerProviderBuilder();
        $meterProviderBuilder = new MeterProviderBuilder();
        $loggerProviderBuilder = new LoggerProviderBuilder();

        // <editor-fold desc="resource and attribute_limits">

        $attributes = $env->map('OTEL_RESOURCE_ATTRIBUTES') ?? [];
        if (($serviceName = $env->string('OTEL_SERVICE_NAME')) !== null) {
            $attributes['service.name'] = $serviceName;
        }
        $resource = Resource::create($attributes);
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $resource = Resource::detect();
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $attributeCountLimit = $env->int('OTEL_ATTRIBUTE_COUNT_LIMIT');
        $attributeValueLengthLimit = $env->int('OTEL_ATTRIBUTE_VALUE_LENGTH_LIMIT');
        $tracerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);
        $loggerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);

        // </editor-fold>

        $configurator = new DisableSelfDiagnosticsConfigurator();
        $tracerProviderBuilder->addTracerConfigurator($configurator);
        $meterProviderBuilder->addMeterConfigurator($configurator);
        $loggerProviderBuilder->addLoggerConfigurator($configurator);

        $tracerProvider = $tracerProviderBuilder->buildBase($logger);
        $meterProvider = $meterProviderBuilder->buildBase($logger);
        $loggerProvider = $loggerProviderBuilder->buildBase($logger);

        $context = new Context(
            tracerProvider: new SelfDiagnostics\TracerProvider($tracerProvider),
            meterProvider: new SelfDiagnostics\MeterProvider($meterProvider),
            loggerProvider: new SelfDiagnostics\LoggerProvider($loggerProvider),
            logger: $logger,
        );

        self::tracerProvider($tracerProviderBuilder, $env, $registry, $context);
        self::meterProvider($meterProviderBuilder, $env, $registry, $context);
        self::loggerProvider($loggerProviderBuilder, $env, $registry, $context);

        $selfDiagnosticsContext = new SelfDiagnosticsContext(
            $context->tracerProvider,
            $context->meterProvider,
            $context->loggerProvider,
        );
        $tracerProviderBuilder->copyStateInto($tracerProvider, $selfDiagnosticsContext);
        $meterProviderBuilder->copyStateInto($meterProvider, $selfDiagnosticsContext);
        $loggerProviderBuilder->copyStateInto($loggerProvider, $selfDiagnosticsContext);

        $propagator = self::propagator($env, $registry, $context);
        $configProperties = self::configProperties($env, $registry, $context);

        $logger->debug('Initialized OTelSDK from env');
        $logger = clone $logger;
        $logger->pushHandler(new LoggerHandler($context->loggerProvider, level: $logLevel));

        return new ConfigurationResult(
            $propagator,
            $tracerProvider,
            $meterProvider,
            $loggerProvider,
            $configProperties,
            $logger,
        );
    }

    private static function propagator(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach (array_unique($env->list('OTEL_PROPAGATORS') ?? ['tracecontext', 'baggage']) as $name) {
            $propagators[] = $registry->load(TextMapPropagatorInterface::class, $name, $env, $context);
        }

        return new MultiTextMapPropagator($propagators);
    }

    private static function configProperties(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): ConfigProperties {
        $configProperties = new ConfigurationRegistry();
        foreach ($registry->loadAll(GeneralInstrumentationConfiguration::class, $env, $context) as $instrumentation) {
            $configProperties->add($instrumentation);
        }
        foreach ($registry->loadAll(InstrumentationConfiguration::class, $env, $context) as $instrumentation) {
            $configProperties->add($instrumentation);
        }

        return $configProperties;
    }

    private static function tracerProvider(TracerProviderBuilder $tracerProviderBuilder, EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): void {
        $tracerProviderBuilder->setSpanAttributeLimits($env->int('OTEL_SPAN_ATTRIBUTE_COUNT_LIMIT'), $env->int('OTEL_SPAN_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $tracerProviderBuilder->setEventCountLimit($env->int('OTEL_SPAN_EVENT_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkCountLimit($env->int('OTEL_SPAN_LINK_COUNT_LIMIT'));
        $tracerProviderBuilder->setEventAttributeLimits($env->int('OTEL_EVENT_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkAttributeLimits($env->int('OTEL_LINK_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setSampler($registry->load(Sampler::class, $env->string('OTEL_TRACES_SAMPLER') ?? 'parentbased_always_on', $env, $context));
        $tracerProviderBuilder->addSpanProcessor($registry->load(SpanProcessor::class, $env->string('OTEL_TRACES_EXPORTER') ?? 'otlp', $env, $context));
    }

    private static function meterProvider(MeterProviderBuilder $meterProviderBuilder, EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): void {
        $meterProviderBuilder->setExemplarFilter($registry->load(ExemplarFilter::class, $env->string('OTEL_METRICS_EXEMPLAR_FILTER') ?? 'trace_based', $env, $context));
        $meterProviderBuilder->addMetricReader($registry->load(MetricReader::class, $env->string('OTEL_METRICS_EXPORTER') ?? 'otlp', $env, $context));
    }

    private static function loggerProvider(LoggerProviderBuilder $loggerProviderBuilder, EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): void {
        $loggerProviderBuilder->setLogRecordAttributeLimits($env->int('OTEL_LOGRECORD_ATTRIBUTE_COUNT_LIMIT'), $env->int('OTEL_LOGRECORD_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $loggerProviderBuilder->addLogRecordProcessor($registry->load(LogRecordProcessor::class, $env->string('OTEL_LOGS_EXPORTER') ?? 'otlp', $env, $context));
    }
}
