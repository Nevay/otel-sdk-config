<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Common\Provider\NoopProvider;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Configuration\Env\ArrayEnvSource;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\EnvSource;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Configuration\Env\MutableLoaderRegistry;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Exception\ConfigurationException;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Metrics\ExemplarReservoirResolver;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
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
        $registry = new MutableLoaderRegistry();
        foreach (ServiceLoader::load(Loader::class) as $loader) {
            $registry->register($loader);
        }
        $env = new EnvResolver(new EnvSourceReader($envSources ?: [
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]));

        $textMapPropagator = $registry->load(TextMapPropagatorInterface::class, 'composite', $env, $context);

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
}
