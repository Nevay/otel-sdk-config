<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use Nevay\OtelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AttributeProcessor\FilteredAttributeProcessor;
use Nevay\OtelSDK\Metrics\InstrumentType;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\View;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @internal
 */
final class MeterProviderBuilderMeterProvider implements ComponentProvider {

    /**
     * @param array{
     *     views: list<array{
     *         stream: array{
     *             name: ?string,
     *             description: ?string,
     *             attribute_keys: list<string>,
     *             aggregation: ?ComponentPlugin<AggregationResolver>,
     *         },
     *         selector: array{
     *             instrument_type: 'counter'|'gauge'|'histogram'|'observable_counter'|'observable_gauge'|'observable_up_down_counter'|'up_down_counter'|null,
     *             instrument_name: ?string,
     *             unit: ?string,
     *             meter_name: ?string,
     *             meter_version: ?string,
     *             meter_schema_url: ?string,
     *         },
     *     }>,
     *     readers: list<ComponentPlugin<MetricReader>>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MeterProviderBuilder {
        $meterProviderBuilder = new MeterProviderBuilder();

        foreach ($properties['views'] as $view) {
            $meterProviderBuilder->addView(
                view: new View(
                    name: $view['stream']['name'],
                    description: $view['stream']['description'],
                    attributeProcessor: $view['stream']['attribute_keys']
                        ? new FilteredAttributeProcessor($view['stream']['attribute_keys'])
                        : null,
                    aggregationResolver: $view['stream']['aggregation']?->create($context),
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
        foreach ($properties['readers'] as $reader) {
            $meterProviderBuilder->addMetricReader($reader->create($context));
        }

        return $meterProviderBuilder;
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('meter_provider');
        $node
            ->children()
                ->arrayNode('views')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('stream')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('name')->defaultNull()->end()
                                    ->scalarNode('description')->defaultNull()->end()
                                    ->arrayNode('attribute_keys')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->append(ComponentPlugin::provider('aggregation', AggregationResolver::class, $registry))
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
                                    ->scalarNode('instrument_name')->defaultNull()->end()
                                    ->scalarNode('unit')->defaultNull()->end()
                                    ->scalarNode('meter_name')->defaultNull()->end()
                                    ->scalarNode('meter_version')->defaultNull()->end()
                                    ->scalarNode('meter_schema_url')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->append(ComponentPlugin::providerList('readers', MetricReader::class, $registry))
            ->end()
        ;

        return $node;
    }
}
