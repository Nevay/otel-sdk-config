<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use InvalidArgumentException;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Common\Configurator\RuleConfiguratorBuilder;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Configuration\ConfigEnv\Attributes\AssociateWithPullMetricReader;
use Nevay\OTelSDK\Configuration\ConfigEnv\Attributes\AssociateWithSimpleLogRecordProcessor;
use Nevay\OTelSDK\Configuration\ConfigEnv\Attributes\AssociateWithSimpleSpanProcessor;
use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Env\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\DebugEnvReader;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvComponentLoaderRegistry;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvResolver;
use Nevay\OTelSDK\Configuration\Internal\LoggerHandler;
use Nevay\OTelSDK\Configuration\SelfDiagnostics\Diagnostics;
use Nevay\OTelSDK\Logs\LoggerConfig;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Logs\LogRecordProcessor\BatchLogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\SimpleLogRecordProcessor;
use Nevay\OTelSDK\Logs\NoopLoggerProvider;
use Nevay\OTelSDK\Metrics\ExemplarFilter;
use Nevay\OTelSDK\Metrics\MeterConfig;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PullMetricReader;
use Nevay\OTelSDK\Metrics\NoopMeterProvider;
use Nevay\OTelSDK\Trace\NoopTracerProvider;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor\BatchSpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\SimpleSpanProcessor;
use Nevay\OTelSDK\Trace\TracerConfig;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\ConfigurationRegistry;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\InstrumentationConfiguration;
use OpenTelemetry\API\Logs\Severity;
use OpenTelemetry\Context\Propagation\MultiResponsePropagator;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use function strcasecmp;
use function strtolower;

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
        $severity = Severity::fromPsr3($logLevel)->value;

        $registry = new EnvComponentLoaderRegistry();
        foreach (ServiceLoader::load(EnvComponentLoader::class) as $loader) {
            $registry->register($loader);
        }

        $env = new EnvResolver(new DebugEnvReader($envReader, $logger), $logger);
        $context = new Context(logger: $logger);

        if ($env->bool('OTEL_SDK_DISABLED') ?? false) {
            $propagator = self::propagator($env, $registry, $context);
            $responsePropagator = self::responsePropagator($env, $registry, $context);
            $configProperties = self::configProperties($env, $registry, $context);

            $logger->debug('Initialized OTelSDK from env', ['disabled' => true]);

            return new ConfigurationResult(
                $propagator,
                $responsePropagator,
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

        $tracerProviderBuilder->addTracerConfigurator((new RuleConfiguratorBuilder())
            ->withRule(static fn(TracerConfig $config) => $config->disabled = true, filter: Diagnostics::isSelfDiagnostics(...))
            ->toConfigurator());
        $meterProviderBuilder->addMeterConfigurator((new RuleConfiguratorBuilder())
            ->withRule(static fn(MeterConfig $config) => $config->disabled = true, filter: Diagnostics::isSelfDiagnostics(...))
            ->toConfigurator());
        $loggerProviderBuilder->addLoggerConfigurator((new RuleConfiguratorBuilder())
            ->withRule(static fn(LoggerConfig $config) => $config->disabled = true, filter: Diagnostics::isSelfDiagnostics(...))
            ->withRule(static fn(LoggerConfig $config) => $config->minimumSeverity = $severity, filter: Diagnostics::isSelfDiagnostics(...))
            ->toConfigurator());

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

        $tracerProviderBuilder->copyStateInto($tracerProvider, $context);
        $meterProviderBuilder->copyStateInto($meterProvider, $context);
        $loggerProviderBuilder->copyStateInto($loggerProvider, $context);

        $propagator = self::propagator($env, $registry, $context);
        $responsePropagator = self::responsePropagator($env, $registry, $context);
        $configProperties = self::configProperties($env, $registry, $context);

        $logger->debug('Initialized OTelSDK from env');
        $logger = clone $logger;
        $logger->pushHandler(new LoggerHandler($context->loggerProvider, level: $logLevel));

        return new ConfigurationResult(
            $propagator,
            $responsePropagator,
            $tracerProvider,
            $meterProvider,
            $loggerProvider,
            $configProperties,
            $logger,
        );
    }

    private static function propagator(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach ($env->list('OTEL_PROPAGATORS') ?? ['tracecontext', 'baggage'] as $name) {
            try {
                $propagators[strtolower($name)] ??= $registry->load(TextMapPropagatorInterface::class, $name, $env, $context);
            } catch (InvalidArgumentException $e) {
                $context->logger->warning('Failed loading propagator: {exception}', ['propagator' => $name, 'exception' => $e]);
            }
        }

        return new MultiTextMapPropagator($propagators);
    }

    private static function responsePropagator(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): ResponsePropagatorInterface {
        $propagators = [];
        foreach ($env->list('OTEL_EXPERIMENTAL_RESPONSE_PROPAGATORS') ?? [] as $name) {
            try {
                $propagators[strtolower($name)] ??= $registry->load(ResponsePropagatorInterface::class, $name, $env, $context);
            } catch (InvalidArgumentException $e) {
                $context->logger->warning('Failed loading response propagator: {exception}', ['propagator' => $name, 'exception' => $e]);
            }
        }

        return new MultiResponsePropagator($propagators);
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

        $samplerName = $env->string('OTEL_TRACES_SAMPLER') ?? 'parentbased_always_on';
        try {
            $tracerProviderBuilder->setSampler($registry->load(Sampler::class, $samplerName, $env, $context));
        } catch (InvalidArgumentException $e) {
            $context->logger->warning('Failed loading sampler: {exception}', ['sampler' => $samplerName, 'exception' => $e]);
        }

        foreach ($env->list('OTEL_TRACES_EXPORTER') ?? ['otlp'] as $exporterName) {
            if (!strcasecmp($exporterName, 'none')) {
                continue;
            }

            try {
                $exporter = $registry->load(SpanExporter::class, $exporterName, $env, $context);
            } catch (InvalidArgumentException $e) {
                $context->logger->warning('Failed loading span exporter: {exception}', ['exporter' => $exporterName, 'exception' => $e]);
                continue;
            }

            $tracerProviderBuilder->addSpanProcessor($registry->loaderHasAttribute(SpanExporter::class, $exporterName, AssociateWithSimpleSpanProcessor::class)
                ? new SimpleSpanProcessor(
                    spanExporter: $exporter,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
                : new BatchSpanProcessor(
                    spanExporter: $exporter,
                    maxQueueSize: $env->int('OTEL_BSP_MAX_QUEUE_SIZE') ?? 2048,
                    scheduledDelayMillis: $env->int('OTEL_BSP_SCHEDULE_DELAY') ?? 5000,
                    exportTimeoutMillis: $env->int('OTEL_BSP_EXPORT_TIMEOUT') ?? 30000,
                    maxExportBatchSize: $env->int('OTEL_BSP_MAX_EXPORT_BATCH_SIZE') ?? 512,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
            );
        }
    }

    private static function meterProvider(MeterProviderBuilder $meterProviderBuilder, EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): void {
        $meterProviderBuilder->setExemplarFilter($registry->load(ExemplarFilter::class, $env->string('OTEL_METRICS_EXEMPLAR_FILTER') ?? 'trace_based', $env, $context));

        foreach ($env->list('OTEL_METRICS_EXPORTER') ?? ['otlp'] as $exporterName) {
            if (!strcasecmp($exporterName, 'none')) {
                continue;
            }

            try {
                $exporter = $registry->load(MetricExporter::class, $exporterName, $env, $context);
            } catch (InvalidArgumentException $e) {
                $context->logger->warning('Failed loading metric exporter: {exception}', ['exporter' => $exporterName, 'exception' => $e]);
                continue;
            }

            $meterProviderBuilder->addMetricReader($registry->loaderHasAttribute(MetricExporter::class, $exporterName, AssociateWithPullMetricReader::class)
                ? new PullMetricReader(
                    metricExporter: $exporter,
                    exportTimeoutMillis: $env->int('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
                : new PeriodicExportingMetricReader(
                    metricExporter: $exporter,
                    exportIntervalMillis: $env->int('OTEL_METRIC_EXPORT_INTERVAL') ?? 60000,
                    exportTimeoutMillis: $env->int('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
            );
        }
    }

    private static function loggerProvider(LoggerProviderBuilder $loggerProviderBuilder, EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): void {
        $loggerProviderBuilder->setLogRecordAttributeLimits($env->int('OTEL_LOGRECORD_ATTRIBUTE_COUNT_LIMIT'), $env->int('OTEL_LOGRECORD_ATTRIBUTE_VALUE_LENGTH_LIMIT'));

        foreach ($env->list('OTEL_LOGS_EXPORTER') ?? ['otlp'] as $exporterName) {
            if (!strcasecmp($exporterName, 'none')) {
                continue;
            }

            try {
                $exporter = $registry->load(LogRecordExporter::class, $exporterName, $env, $context);
            } catch (InvalidArgumentException $e) {
                $context->logger->warning('Failed loading logrecord exporter: {exception}', ['exporter' => $exporterName, 'exception' => $e]);
                continue;
            }

            $loggerProviderBuilder->addLogRecordProcessor($registry->loaderHasAttribute(LogRecordExporter::class, $exporterName, AssociateWithSimpleLogRecordProcessor::class)
                ? new SimpleLogRecordProcessor(
                    logRecordExporter: $exporter,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
                : new BatchLogRecordProcessor(
                    logRecordExporter: $exporter,
                    maxQueueSize: $env->int('OTEL_BLRP_MAX_QUEUE_SIZE') ?? 2048,
                    scheduledDelayMillis: $env->int('OTEL_BLRP_SCHEDULE_DELAY') ?? 5000,
                    exportTimeoutMillis: $env->int('OTEL_BLRP_EXPORT_TIMEOUT') ?? 30000,
                    maxExportBatchSize: $env->int('OTEL_BLRP_MAX_EXPORT_BATCH_SIZE') ?? 512,
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                )
            );
        }
    }
}
