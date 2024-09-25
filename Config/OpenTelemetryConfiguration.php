<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Common\Provider\NoopProvider;
use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\ConfigurationProcessor;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Metrics\Aggregation;
use Nevay\OTelSDK\Metrics\InstrumentType;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\MetricProducer;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PullMetricReader;
use Nevay\OTelSDK\Metrics\View;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Logs\NoopEventLoggerProvider;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\Context\Propagation\NoopTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\SDK\Metrics\NoopMeterProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class OpenTelemetryConfiguration implements ComponentProvider {

    public function __construct(
        private readonly ?ConfigurationProcessor $processor = null,
    ) {}

    /**
     * @param array{
     *     file_format: '0.1',
     *     disabled: bool,
     *     resource: array{
     *         attributes: list<array{
     *             name: string,
     *             value: mixed,
     *         }>,
     *         attributes_list: ?string,
     *         schema_url: ?string,
     *         detectors: array{
     *             included: list<string>,
     *             excluded: list<string>,
     *         },
     *     },
     *     attribute_limits: array{
     *         attribute_value_length_limit: ?int<0, max>,
     *         attribute_count_limit: ?int<0, max>,
     *     },
     *     propagator: ?ComponentPlugin<TextMapPropagatorInterface>,
     *     tracer_provider: array{
     *         limits: array{
     *             attribute_value_length_limit: ?int<0, max>,
     *             attribute_count_limit: ?int<0, max>,
     *             event_count_limit: int<0, max>,
     *             link_count_limit: int<0, max>,
     *             event_attribute_count_limit: ?int<0, max>,
     *             link_attribute_count_limit: ?int<0, max>,
     *         },
     *         sampler: ?ComponentPlugin<Sampler>,
     *         processors: list<ComponentPlugin<SpanProcessor>>,
     *     },
     *     meter_provider: array{
     *         views: list<array{
     *             stream: array{
     *                 name: ?string,
     *                 description: ?string,
     *                 attribute_keys: array{
     *                     included: list<string>,
     *                     excluded: list<string>,
     *                 },
     *                 aggregation: ?ComponentPlugin<Aggregation>,
     *             },
     *             selector: array{
     *                 instrument_type: 'counter'|'gauge'|'histogram'|'observable_counter'|'observable_gauge'|'observable_up_down_counter'|'up_down_counter'|null,
     *                 instrument_name: ?string,
     *                 unit: ?string,
     *                 meter_name: ?string,
     *                 meter_version: ?string,
     *                 meter_schema_url: ?string,
     *             },
     *         }>,
     *         readers: list<array{
     *             pull?: array{
     *                 exporter: ComponentPlugin<MetricExporter>,
     *             },
     *             periodic?: array{
     *                 interval: int<0, max>,
     *                 timeout: int<0, max>,
     *                 exporter: ComponentPlugin<MetricExporter>,
     *             },
     *             producers: list<ComponentPlugin<MetricProducer>>,
     *         }>,
     *     },
     *     logger_provider: array{
     *         limits: array{
     *             attribute_value_length_limit: ?int<0, max>,
     *             attribute_count_limit: int<0, max>,
     *         },
     *         processors: list<ComponentPlugin<LogRecordProcessor>>,
     *     },
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ConfigurationResult {
        $propagator = $properties['propagator']?->create($context) ?? NoopTextMapPropagator::getInstance();

        if ($properties['disabled']) {
            return new ConfigurationResult(
                $propagator,
                new NoopTracerProvider(),
                new NoopMeterProvider(),
                new NoopLoggerProvider(),
                new NoopEventLoggerProvider(),
                new NoopProvider(),
            );
        }

        $tracerProviderBuilder = new TracerProviderBuilder();
        $meterProviderBuilder = new MeterProviderBuilder();
        $loggerProviderBuilder = new LoggerProviderBuilder();

        $resource = Resource::create(
            Util::parseMapList($properties['resource']['attributes'], $properties['resource']['attributes_list']),
            $properties['resource']['schema_url'],
        );
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $attributeCountLimit = $properties['attribute_limits']['attribute_count_limit'];
        $attributeValueLengthLimit = $properties['attribute_limits']['attribute_value_length_limit'];
        $tracerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);
        $loggerProviderBuilder->setAttributeLimits($attributeCountLimit, $attributeValueLengthLimit);

        // <editor-fold desc="tracer_provider">

        $tracerProviderBuilder->setSpanAttributeLimits(
            $properties['tracer_provider']['limits']['attribute_count_limit'],
            $properties['tracer_provider']['limits']['attribute_count_limit'],
        );
        $tracerProviderBuilder->setEventCountLimit($properties['tracer_provider']['limits']['event_count_limit']);
        $tracerProviderBuilder->setLinkCountLimit($properties['tracer_provider']['limits']['link_count_limit']);
        $tracerProviderBuilder->setEventAttributeLimits($properties['tracer_provider']['limits']['event_attribute_count_limit']);
        $tracerProviderBuilder->setLinkAttributeLimits($properties['tracer_provider']['limits']['link_attribute_count_limit']);
        $tracerProviderBuilder->setSampler($properties['tracer_provider']['sampler']?->create($context));
        foreach ($properties['tracer_provider']['processors'] as $processor) {
            $tracerProviderBuilder->addSpanProcessor($processor->create($context));
        }

        // </editor-fold>

        // <editor-fold desc="meter_provider">

        foreach ($properties['meter_provider']['views'] as $view) {
            $meterProviderBuilder->addView(
                view: new View(
                    name: $view['stream']['name'],
                    description: $view['stream']['description'],
                    attributeKeys: !isset($view['stream']['attribute_keys']) ? null : Attributes::filterKeys(
                        include: $view['stream']['attribute_keys']['included'] ?: '*',
                        exclude: $view['stream']['attribute_keys']['excluded'] ?: [],
                    ),
                    aggregation: $view['stream']['aggregation']?->create($context),
                ),
                type: match ($view['selector']['instrument_type']) {
                    'counter' => InstrumentType::Counter,
                    'gauge' => InstrumentType::Gauge,
                    'histogram' => InstrumentType::Histogram,
                    'observable_counter' => InstrumentType::AsynchronousCounter,
                    'observable_gauge' => InstrumentType::AsynchronousGauge,
                    'observable_up_down_counter' => InstrumentType::AsynchronousUpDownCounter,
                    'up_down_counter' => InstrumentType::UpDownCounter,
                    null => null,
                },
                name: $view['selector']['instrument_name'],
                unit: $view['selector']['unit'],
                meterName: $view['selector']['meter_name'],
                meterVersion: $view['selector']['meter_version'],
                meterSchemaUrl: $view['selector']['meter_schema_url'],

            );
        }
        foreach ($properties['meter_provider']['readers'] as $reader) {
            $metricReader = match (true) {
                isset($reader['pull']) => new PullMetricReader(
                    metricExporter: $reader['pull']['exporter']->create($context),
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                ),
                isset($reader['periodic']) => new PeriodicExportingMetricReader(
                    metricExporter: $reader['periodic']['exporter']->create($context),
                    exportIntervalMillis: $reader['periodic']['interval'],
                    exportTimeoutMillis: $reader['periodic']['timeout'],
                    tracerProvider: $context->tracerProvider,
                    meterProvider: $context->meterProvider,
                    logger: $context->logger,
                ),
            };

            foreach ($reader['producers'] as $producer) {
                $metricReader->registerProducer($producer->create($context));
            }

            $meterProviderBuilder->addMetricReader($metricReader);
        }

        // </editor-fold>

        // <editor-fold desc="logger_provider">

        $loggerProviderBuilder->setLogRecordAttributeLimits(
            $properties['logger_provider']['limits']['attribute_count_limit'],
            $properties['logger_provider']['limits']['attribute_count_limit'],
        );
        foreach ($properties['logger_provider']['processors'] as $processor) {
            $loggerProviderBuilder->addLogRecordProcessor($processor->create($context));
        }

        // </editor-fold>

        $this->processor?->process($tracerProviderBuilder, $meterProviderBuilder, $loggerProviderBuilder);

        $resource = Resource::detect(
            include: $properties['resource']['detectors']['attributes']['included'] ?? null ?: '*',
            exclude: $properties['resource']['detectors']['attributes']['excluded'] ?? null ?: [],
        );
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);

        $tracerProvider = $tracerProviderBuilder->build($context->logger);
        $meterProvider = $meterProviderBuilder->build($context->logger);
        $loggerProvider = $loggerProviderBuilder->build($context->logger);

        return new ConfigurationResult(
            $propagator,
            $tracerProvider,
            $meterProvider,
            $loggerProvider,
            $loggerProvider,
            new MultiProvider([
                $tracerProvider,
                $meterProvider,
                $loggerProvider,
            ]),
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('open_telemetry');
        $node
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('file_format')
                    ->isRequired()
                    ->example('0.1')
                    ->validate()->always(Validation::ensureString())->end()
                    ->validate()->ifNotInArray(['0.1'])->thenInvalid('unsupported version')->end()
                ->end()
                ->booleanNode('disabled')->defaultFalse()->end()
                ->append($this->getResourceConfig())
                ->append($this->getAttributeLimitsConfig())
                ->append($registry->component('propagator', TextMapPropagatorInterface::class))
                ->append($this->getTracerProviderConfig($registry))
                ->append($this->getMeterProviderConfig($registry))
                ->append($this->getLoggerProviderConfig($registry))
            ->end();

        return $node;
    }

    private function getResourceConfig(): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('resource');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('attributes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->validate()->always(Validation::ensureString())->end()->end()
                            ->variableNode('value')->isRequired()->end()
                            ->scalarNode('type')->defaultValue('string')->validate()->always(Validation::ensureString())->end()->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('attributes_list')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                ->arrayNode('detectors')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attributes')
                            ->children()
                                ->arrayNode('included')->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                                ->arrayNode('excluded')->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('schema_url')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
            ->end();

        return $node;
    }

    private function getAttributeLimitsConfig(): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('attribute_limits');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('attribute_value_length_limit')->min(0)->defaultNull()->end()
                ->integerNode('attribute_count_limit')->min(0)->defaultValue(128)->end()
            ->end();

        return $node;
    }

    private function getTracerProviderConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('tracer_provider');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('attribute_value_length_limit')->min(0)->defaultNull()->end()
                        ->integerNode('attribute_count_limit')->min(0)->defaultNull()->end()
                        ->integerNode('event_count_limit')->min(0)->defaultValue(128)->end()
                        ->integerNode('link_count_limit')->min(0)->defaultValue(128)->end()
                        ->integerNode('event_attribute_count_limit')->min(0)->defaultNull()->end()
                        ->integerNode('link_attribute_count_limit')->min(0)->defaultNull()->end()
                    ->end()
                ->end()
                ->append($registry->component('sampler', Sampler::class))
                ->append($registry->componentList('processors', SpanProcessor::class))
            ->end()
        ;

        return $node;
    }

    private function getMeterProviderConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('meter_provider');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('views')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('stream')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('name')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->scalarNode('description')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->arrayNode('attribute_keys')
                                        ->children()
                                            ->arrayNode('included')->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                                            ->arrayNode('excluded')->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()->end()
                                        ->end()
                                    ->end()
                                    ->append($registry->component('aggregation', Aggregation::class))
                                ->end()
                            ->end()
                            ->arrayNode('selector')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->enumNode('instrument_type')
                                        ->values([
                                            'counter',
                                            'gauge',
                                            'histogram',
                                            'observable_counter',
                                            'observable_gauge',
                                            'observable_up_down_counter',
                                            'up_down_counter',
                                        ])
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('instrument_name')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->scalarNode('unit')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->scalarNode('meter_name')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->scalarNode('meter_version')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                    ->scalarNode('meter_schema_url')->defaultNull()->validate()->always(Validation::ensureString())->end()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('readers')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('pull')
                                ->children()
                                    ->append($registry->component('exporter', MetricExporter::class)->isRequired())
                                ->end()
                            ->end()
                            ->arrayNode('periodic')
                                ->children()
                                    ->integerNode('interval')->min(0)->defaultValue(5000)->end()
                                    ->integerNode('timeout')->min(0)->defaultValue(30000)->end()
                                    ->append($registry->component('exporter', MetricExporter::class)->isRequired())
                                ->end()
                            ->end()
                            ->append($registry->componentList('producers', MetricProducer::class))
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getLoggerProviderConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('logger_provider');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('attribute_value_length_limit')->min(0)->defaultNull()->end()
                        ->integerNode('attribute_count_limit')->min(0)->defaultNull()->end()
                    ->end()
                ->end()
                ->append($registry->componentList('processors', LogRecordProcessor::class))
            ->end()
        ;

        return $node;
    }
}
