<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use InvalidArgumentException;
use Nevay\OtelSDK\Common\Provider\MultiProvider;
use Nevay\OtelSDK\Common\Provider\NoopProvider;
use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs\LogRecordExporterLoaderConsole;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs\LogRecordExporterLoaderOtlp;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs\LogRecordProcessorLoaderBatch;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs\LogRecordProcessorLoaderSimple;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\AggregationLoaderDefault;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\AggregationLoaderDrop;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\AggregationLoaderExplicitBucketHistogram;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\AggregationLoaderLastValue;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\AggregationLoaderSum;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\MetricExporterLoaderConsole;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\MetricExporterLoaderOtlp;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\MetricExporterLoaderPrometheus;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\MetricReaderLoaderPeriodic;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\MetricReaderLoaderPull;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\TemporalityResolverLoaderConsole;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\TemporalityResolverLoaderOtlp;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\TemporalityResolverLoaderPeriodic;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\TemporalityResolverLoaderPrometheus;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics\TemporalityResolverLoaderPull;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderB3;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderB3Multi;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderBaggage;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderComposite;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderJaeger;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator\TextMapPropagatorLoaderTraceContext;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SamplerLoaderAlwaysOff;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SamplerLoaderAlwaysOn;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SamplerLoaderParentBased;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SamplerLoaderTraceIdRatioBased;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SpanExporterLoaderConsole;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SpanExporterLoaderOtlp;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SpanProcessorLoaderBatch;
use Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace\SpanProcessorLoaderSimple;
use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Config\MutableLoaderRegistry;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Logs\LogRecordProcessor;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AttributeProcessor\FilteredAttributeProcessor;
use Nevay\OtelSDK\Metrics\InstrumentType;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\TemporalityResolver;
use Nevay\OtelSDK\Metrics\View;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OtelSDK\Trace\Sampler\ParentBasedSampler;
use Nevay\OtelSDK\Trace\SpanProcessor;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Metrics\Noop\NoopMeterProvider;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\Context\Propagation\NoopTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use function sprintf;

final class Config {

    /**
     * @throws ConfigurationException
     */
    public static function load(
        array $config,
        Context $context = new Context(),
    ): ConfigurationResult {
        if (($fileFormat = $config['file_format'] ?? null) !== '0.1') {
            throw new InvalidArgumentException(sprintf('Unsupported file format "%s", expected version 0.1', $fileFormat));
        }

        $registry = self::registry();

        $textMapPropagator = $registry->loadNullable(TextMapPropagatorInterface::class, $config['propagator'] ?? null, $context) ?? NoopTextMapPropagator::getInstance();

        if ($config['disabled'] ?? false) {
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

        $resource = Resource::create(
            $config['resource']['attributes'] ?? [],
            $config['resource']['schema_url'] ?? null,
        );
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $attributeCountLimit = $config['attribute_limits']['attribute_count_limit'] ?? null;
        $attributeValueLengthLimit = $config['attribute_limits']['attribute_value_length_limit'] ?? null;
        $tracerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);
        $loggerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);

        self::tracerProvider($tracerProviderBuilder, $config['tracer_provider'] ?? [], $registry, $context);
        self::meterProvider($meterProviderBuilder, $config['meter_provider'] ?? [], $registry, $context);
        self::loggerProvider($loggerProviderBuilder, $config['logger_provider'] ?? [], $registry, $context);

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

    private static function tracerProvider(TracerProviderBuilder $tracerProviderBuilder, array $config, LoaderRegistry $registry, Context $context): void {
        $tracerProviderBuilder->setSpanAttributeLimits(
            $config['limits']['attribute_count_limit'] ?? null,
            $config['limits']['attribute_value_length_limit'] ?? null,
        );
        $tracerProviderBuilder->setEventCountLimit($config['limits']['event_count_limit'] ?? null);
        $tracerProviderBuilder->setLinkCountLimit($config['limits']['link_count_limit'] ?? null);
        $tracerProviderBuilder->setEventAttributeLimits($config['limits']['event_attribute_count_limit'] ?? null);
        $tracerProviderBuilder->setLinkAttributeLimits($config['limits']['link_attribute_count_limit'] ?? null);
        $tracerProviderBuilder->setSampler($registry->loadNullable(Sampler::class, $config['sampler'] ?? null, $context) ?? new ParentBasedSampler(new AlwaysOnSampler()));
        foreach ($config['processors'] ?? [] as $processor) {
            $tracerProviderBuilder->addSpanProcessor($registry->load(SpanProcessor::class, $processor, $context));
        }
    }

    private static function meterProvider(MeterProviderBuilder $meterProviderBuilder, array $config, LoaderRegistry $registry, Context $context): void {
        foreach ($config['views'] ?? [] as $view) {
            $meterProviderBuilder->addView(
                view: new View(
                    name: $view['stream']['name'] ?? null,
                    description: $view['stream']['description'] ?? null,
                    attributeProcessor: isset($view['stream']['attribute_keys'])
                        ? new FilteredAttributeProcessor($view['stream']['attribute_keys'])
                        : null,
                    aggregationResolver: $registry->loadNullable(AggregationResolver::class, $view['stream']['aggregation'] ?? null, $context),
                ),
                type: match ($view['selector']['instrument_type'] ?? null) {
                    'counter' => InstrumentType::Counter,
                    'gauge' => InstrumentType::Gauge,
                    'histogram' => InstrumentType::Histogram,
                    'observable_counter' => InstrumentType::AsynchronousCounter,
                    'observable_gauge' => InstrumentType::AsynchronousGauge,
                    'observable_up_down_counter' => InstrumentType::AsynchronousUpDownCounter,
                    'up_down_counter' => InstrumentType::UpDownCounter,
                    null => null,
                },
                name: $view['selector']['instrument_name'] ?? null,
                unit: $view['selector']['unit'] ?? null,
                meterName: $view['selector']['meter_name'] ?? null,
                meterVersion: $view['selector']['meter_version'] ?? null,
                meterSchemaUrl: $view['selector']['meter_schema_url'] ?? null,
            );
        }
        foreach ($config['readers'] ?? [] as $reader) {
            $metricReader = $registry->load(MetricReader::class, $reader, $context);
            $temporalityResolver = $registry->load(TemporalityResolver::class, $reader, $context);
            $meterProviderBuilder->addMetricReader($metricReader, $temporalityResolver);
        }
    }

    private static function loggerProvider(LoggerProviderBuilder $loggerProviderBuilder, array $config, LoaderRegistry $registry, Context $context): void {
        $loggerProviderBuilder->setLogRecordAttributeLimits(
            $config['limits']['attribute_count_limit'] ?? null,
            $config['limits']['attribute_value_length_limit'] ?? null,
        );
        foreach ($config['processors'] ?? [] as $processor) {
            $loggerProviderBuilder->addLogRecordProcessor($registry->load(LogRecordProcessor::class, $processor, $context));
        }
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
        $registry->register(new TextMapPropagatorLoaderComposite());
        $registry->register(new TextMapPropagatorLoaderJaeger());
        $registry->register(new TextMapPropagatorLoaderTraceContext());

        // trace
        $registry->register(new SamplerLoaderAlwaysOff());
        $registry->register(new SamplerLoaderAlwaysOn());
        $registry->register(new SamplerLoaderParentBased());
        $registry->register(new SamplerLoaderTraceIdRatioBased());
        $registry->register(new SpanExporterLoaderConsole());
        $registry->register(new SpanExporterLoaderOtlp());
        $registry->register(new SpanProcessorLoaderBatch());
        $registry->register(new SpanProcessorLoaderSimple());

        // metrics
        $registry->register(new AggregationLoaderDefault());
        $registry->register(new AggregationLoaderDrop());
        $registry->register(new AggregationLoaderExplicitBucketHistogram());
        $registry->register(new AggregationLoaderLastValue());
        $registry->register(new AggregationLoaderSum());
        $registry->register(new MetricExporterLoaderConsole());
        $registry->register(new MetricExporterLoaderOtlp());
        $registry->register(new MetricExporterLoaderPrometheus());
        $registry->register(new MetricReaderLoaderPeriodic());
        $registry->register(new MetricReaderLoaderPull());
        $registry->register(new TemporalityResolverLoaderConsole());
        $registry->register(new TemporalityResolverLoaderOtlp());
        $registry->register(new TemporalityResolverLoaderPeriodic());
        $registry->register(new TemporalityResolverLoaderPrometheus());
        $registry->register(new TemporalityResolverLoaderPull());

        // logs
        $registry->register(new LogRecordExporterLoaderConsole());
        $registry->register(new LogRecordExporterLoaderOtlp());
        $registry->register(new LogRecordProcessorLoaderBatch());
        $registry->register(new LogRecordProcessorLoaderSimple());

        foreach (ServiceLoader::load(Loader::class) as $loader) {
            $registry->register($loader);
        }

        return $registry;
    }
}
