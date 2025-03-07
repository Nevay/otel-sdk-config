<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Common\Configurator\RuleConfiguratorBuilder;
use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Common\Provider\NoopProvider;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Configuration\Env\MutableLoaderRegistry;
use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Environment\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Logging\LoggerHandler;
use Nevay\OTelSDK\Logs\LoggerConfig;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\NoopLoggerProvider;
use Nevay\OTelSDK\Metrics\ExemplarFilter;
use Nevay\OTelSDK\Metrics\MeterConfig;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\NoopMeterProvider;
use Nevay\OTelSDK\Trace\NoopTracerProvider;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\TracerConfig;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\Noop\NoopConfigProperties;
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

        $env = new EnvResolver($envReader, $logger);
        $context = new Context(logger: $logger);

        $registry = new MutableLoaderRegistry();
        foreach (ServiceLoader::load(Loader::class) as $loader) {
            $registry->register($loader);
        }

        $propagators = [];
        foreach (array_unique($env->list('OTEL_PROPAGATORS') ?? ['tracecontext', 'baggage']) as $name) {
            $propagators[] = $registry->load(TextMapPropagatorInterface::class, $name, $env, $context);
        }
        $textMapPropagator = new MultiTextMapPropagator($propagators);

        if ($env->bool('OTEL_SDK_DISABLED') ?? false) {
            return new ConfigurationResult(
                $textMapPropagator,
                new NoopTracerProvider(),
                new NoopMeterProvider(),
                new NoopLoggerProvider(),
                new NoopProvider(),
                new NoopConfigProperties(),
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

        $configurator = (new RuleConfiguratorBuilder())
            ->withRule(
                configurator: static fn(TracerConfig|MeterConfig|LoggerConfig $config) => $config->disabled = true,
                name: 'com.tobiasbachert.otel.sdk.*',
            )
            ->toConfigurator();

        $tracerProviderBuilder->addTracerConfigurator($configurator);
        $meterProviderBuilder->addMeterConfigurator($configurator);
        $loggerProviderBuilder->addLoggerConfigurator($configurator);

        $tracerProvider = $tracerProviderBuilder->buildBase($logger);
        $meterProvider = $meterProviderBuilder->buildBase($logger);
        $loggerProvider = $loggerProviderBuilder->buildBase($logger);

        $context = new Context(
            tracerProvider: $tracerProvider,
            meterProvider: $meterProvider,
            loggerProvider: $loggerProvider,
            logger: $logger,
        );

        self::tracerProvider($tracerProviderBuilder, $env, $registry, $context);
        self::meterProvider($meterProviderBuilder, $env, $registry, $context);
        self::loggerProvider($loggerProviderBuilder, $env, $registry, $context);

        $logger = clone $logger;
        $logger->pushHandler(new LoggerHandler($loggerProvider, level: $logLevel));

        $tracerProviderBuilder->copyStateInto($tracerProvider);
        $meterProviderBuilder->copyStateInto($meterProvider);
        $loggerProviderBuilder->copyStateInto($loggerProvider);

        return new ConfigurationResult(
            $textMapPropagator,
            $tracerProvider,
            $meterProvider,
            $loggerProvider,
            new MultiProvider([
                $tracerProvider,
                $meterProvider,
                $loggerProvider,
            ]),
            new NoopConfigProperties(),
            $logger,
        );
    }

    private static function tracerProvider(TracerProviderBuilder $tracerProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $tracerProviderBuilder->setSpanAttributeLimits($env->int('OTEL_SPAN_ATTRIBUTE_COUNT_LIMIT'), $env->int('OTEL_SPAN_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $tracerProviderBuilder->setEventCountLimit($env->int('OTEL_SPAN_EVENT_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkCountLimit($env->int('OTEL_SPAN_LINK_COUNT_LIMIT'));
        $tracerProviderBuilder->setEventAttributeLimits($env->int('OTEL_EVENT_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkAttributeLimits($env->int('OTEL_LINK_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setSampler($registry->load(Sampler::class, $env->string('OTEL_TRACES_SAMPLER') ?? 'parentbased_always_on', $env, $context));
        $tracerProviderBuilder->addSpanProcessor($registry->load(SpanProcessor::class, $env->string('OTEL_TRACES_EXPORTER') ?? 'otlp', $env, $context));
    }

    private static function meterProvider(MeterProviderBuilder $meterProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $meterProviderBuilder->setExemplarFilter($registry->load(ExemplarFilter::class, $env->string('OTEL_METRICS_EXEMPLAR_FILTER') ?? 'trace_based', $env, $context));
        $meterProviderBuilder->addMetricReader($registry->load(MetricReader::class, $env->string('OTEL_METRICS_EXPORTER') ?? 'otlp', $env, $context));
    }

    private static function loggerProvider(LoggerProviderBuilder $loggerProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $loggerProviderBuilder->setLogRecordAttributeLimits($env->int('OTEL_LOGRECORD_ATTRIBUTE_COUNT_LIMIT'), $env->int('OTEL_LOGRECORD_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $loggerProviderBuilder->addLogRecordProcessor($registry->load(LogRecordProcessor::class, $env->string('OTEL_LOGS_EXPORTER') ?? 'otlp', $env, $context));
    }
}
