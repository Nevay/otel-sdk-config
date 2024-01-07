<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Common\Provider\MultiProvider;
use Nevay\OtelSDK\Common\Provider\NoopProvider;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Configuration\Env\ArrayEnvSource;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Logs\LogRecordExporterLoaderConsole;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Logs\LogRecordExporterLoaderNone;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Logs\LogRecordExporterLoaderOtlp;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Logs\LogRecordProcessorLoaderBatch;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\ExemplarReservoirResolverLoaderAlwaysOff;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\ExemplarReservoirResolverLoaderAlwaysOn;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\ExemplarReservoirResolverLoaderTraceBased;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\MetricReaderLoaderConsole;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\MetricReaderLoaderNone;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\MetricReaderLoaderOtlp;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics\MetricReaderLoaderPrometheus;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderB3;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderB3Multi;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderBaggage;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderJaeger;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderPropagators;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator\TextMapPropagatorLoaderTraceContext;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderAlwaysOff;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderAlwaysOn;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderParentBasedAlwaysOff;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderParentBasedAlwaysOn;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderParentBasedTraceIdRatio;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SamplerLoaderTraceIdRatio;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SpanExporterLoaderConsole;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SpanExporterLoaderNone;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SpanExporterLoaderOtlp;
use Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace\SpanProcessorLoaderBatch;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\EnvSource;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Env\MutableLoaderRegistry;
use Nevay\OtelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Logs\LogRecordProcessor;
use Nevay\OtelSDK\Metrics\ExemplarReservoirResolver;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\SpanProcessor;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Metrics\Noop\NoopMeterProvider;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

final class Env {

    /**
     * @throws ConfigurationException
     */
    public static function load(
        Context $context = new Context(),
        EnvSource ...$envSources,
    ): ConfigurationResult {
        $registry = self::registry();
        $env = new EnvResolver($envSources ?: [
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]);

        $textMapPropagator = $registry->load(TextMapPropagatorInterface::class, 'propagators', $env, $context);

        if ($env->bool('OTEL_SDK_DISABLED') ?? false) {
            return new ConfigurationResult(
                $textMapPropagator,
                new NoopTracerProvider(),
                new NoopMeterProvider(),
                new NoopLoggerProvider(),
                new NoopProvider(),
            );
        }


        $tracerProviderBuilder = new TracerProviderBuilder();
        $meterProviderBuilder = new MeterProviderBuilder();
        $loggerProviderBuilder = new LoggerProviderBuilder();

        $attributes = $env->map('OTEL_RESOURCE_ATTRIBUTES') ?? [];
        if (($serviceName = $env->string('OTEL_SERVICE_NAME')) !== null) {
            $attributes['service.name'] = $serviceName;
        }
        $resource = Resource::create($attributes);
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $attributeCountLimit = $env->numeric('OTEL_ATTRIBUTE_COUNT_LIMIT');
        $attributeValueLengthLimit = $env->numeric('OTEL_ATTRIBUTE_VALUE_LENGTH_LIMIT');
        $tracerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);
        $loggerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);

        self::tracerProvider($tracerProviderBuilder, $env, $registry, $context);
        self::meterProvider($meterProviderBuilder, $env, $registry, $context);
        self::loggerProvider($loggerProviderBuilder, $env, $registry, $context);

        $context->processor?->process($tracerProviderBuilder, $meterProviderBuilder, $loggerProviderBuilder);

        $resource = Resource::default();
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $tracerProvider = $tracerProviderBuilder->build($context->logger);
        $meterProvider = $meterProviderBuilder->build($context->logger);
        $loggerProvider = $loggerProviderBuilder->build($context->logger);

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
        );
    }

    private static function tracerProvider(TracerProviderBuilder $tracerProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $tracerProviderBuilder->setSpanAttributeLimits($env->numeric('OTEL_SPAN_ATTRIBUTE_COUNT_LIMIT'), $env->numeric('OTEL_SPAN_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $tracerProviderBuilder->setEventCountLimit($env->numeric('OTEL_SPAN_EVENT_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkCountLimit($env->numeric('OTEL_SPAN_LINK_COUNT_LIMIT'));
        $tracerProviderBuilder->setEventAttributeLimits($env->numeric('OTEL_EVENT_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setLinkAttributeLimits($env->numeric('OTEL_LINK_ATTRIBUTE_COUNT_LIMIT'));
        $tracerProviderBuilder->setSampler($registry->load(Sampler::class, $env->string('OTEL_TRACES_SAMPLER') ?? 'parentbased_always_on', $env, $context));
        $tracerProviderBuilder->addSpanProcessor($registry->load(SpanProcessor::class, $env->string('OTEL_PHP_TRACES_PROCESSOR') ?? 'batch', $env, $context));
    }

    private static function meterProvider(MeterProviderBuilder $meterProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $meterProviderBuilder->setExemplarReservoirResolver($registry->load(ExemplarReservoirResolver::class, $env->string('OTEL_METRICS_EXEMPLAR_FILTER') ?? 'trace_based', $env, $context));
        $meterProviderBuilder->addMetricReader($registry->load(MetricReader::class, $env->string('OTEL_METRICS_EXPORTER') ?? 'otlp', $env, $context));
    }

    private static function loggerProvider(LoggerProviderBuilder $loggerProviderBuilder, EnvResolver $env, LoaderRegistry $registry, Context $context): void {
        $loggerProviderBuilder->setLogRecordAttributeLimits($env->numeric('OTEL_LOGRECORD_ATTRIBUTE_COUNT_LIMIT'), $env->numeric('OTEL_LOGRECORD_ATTRIBUTE_VALUE_LENGTH_LIMIT'));
        $loggerProviderBuilder->addLogRecordProcessor($registry->load(LogRecordProcessor::class, $env->string('OTEL_PHP_LOGS_PROCESSOR') ?? 'batch', $env, $context));
    }

    private static function registry(): LoaderRegistry {
        static $registry;
        if ($registry) {
            return $registry;
        }

        $registry = new MutableLoaderRegistry();

        // propagators
        $registry->register(new TextMapPropagatorLoaderB3());
        $registry->register(new TextMapPropagatorLoaderB3Multi());
        $registry->register(new TextMapPropagatorLoaderBaggage());
        $registry->register(new TextMapPropagatorLoaderJaeger());
        $registry->register(new TextMapPropagatorLoaderPropagators());
        $registry->register(new TextMapPropagatorLoaderTraceContext());

        // trace
        $registry->register(new SamplerLoaderAlwaysOff());
        $registry->register(new SamplerLoaderAlwaysOn());
        $registry->register(new SamplerLoaderParentBasedAlwaysOff());
        $registry->register(new SamplerLoaderParentBasedAlwaysOn());
        $registry->register(new SamplerLoaderParentBasedTraceIdRatio());
        $registry->register(new SamplerLoaderTraceIdRatio());
        $registry->register(new SpanExporterLoaderConsole());
        $registry->register(new SpanExporterLoaderNone());
        $registry->register(new SpanExporterLoaderOtlp());
        $registry->register(new SpanProcessorLoaderBatch());

        // metrics
        $registry->register(new ExemplarReservoirResolverLoaderAlwaysOff());
        $registry->register(new ExemplarReservoirResolverLoaderAlwaysOn());
        $registry->register(new ExemplarReservoirResolverLoaderTraceBased());
        $registry->register(new MetricReaderLoaderConsole());
        $registry->register(new MetricReaderLoaderNone());
        $registry->register(new MetricReaderLoaderOtlp());
        $registry->register(new MetricReaderLoaderPrometheus());

        // logs
        $registry->register(new LogRecordExporterLoaderConsole());
        $registry->register(new LogRecordExporterLoaderNone());
        $registry->register(new LogRecordExporterLoaderOtlp());
        $registry->register(new LogRecordProcessorLoaderBatch());

        foreach (ServiceLoader::load(Loader::class) as $loader) {
            $registry->register($loader);
        }

        return $registry;
    }
}
