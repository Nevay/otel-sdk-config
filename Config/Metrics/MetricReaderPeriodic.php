<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Metrics\CardinalityLimitResolver\CardinalityLimitResolver;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\MetricProducer;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function array_map;

final class MetricReaderPeriodic implements ComponentProvider {

    /**
     * @param array{
     *     interval: int<0, max>,
     *     timeout: int<0, max>,
     *     exporter: ComponentPlugin<MetricExporter>,
     *     producers: list<ComponentPlugin<MetricProducer>>,
     *     cardinality_limits: array{
     *         default: ?int<1,max>,
     *         counter: ?int<1,max>,
     *         gauge: ?int<1,max>,
     *         histogram: ?int<1,max>,
     *         observable_counter: ?int<1,max>,
     *         observable_gauge: ?int<1,max>,
     *         observable_up_down_counter: ?int<1,max>,
     *         up_down_counter: ?int<1,max>,
     *     },
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: $properties['exporter']->create($context),
            exportIntervalMillis: $properties['interval'],
            exportTimeoutMillis: $properties['timeout'],
            cardinalityLimits: new CardinalityLimitResolver(
                default: $properties['cardinality_limits']['default'],
                counter: $properties['cardinality_limits']['counter'],
                upDownCounter: $properties['cardinality_limits']['up_down_counter'],
                histogram: $properties['cardinality_limits']['histogram'],
                gauge: $properties['cardinality_limits']['gauge'],
                asynchronousCounter: $properties['cardinality_limits']['observable_counter'],
                asynchronousUpDownCounter: $properties['cardinality_limits']['observable_up_down_counter'],
                asynchronousGauge: $properties['cardinality_limits']['observable_gauge'],
            ),
            metricProducers: array_map(static fn(ComponentPlugin $producer) => $producer->create($context), $properties['producers']),
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('periodic');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('interval')->min(0)->defaultValue(60000)->end()
                ->integerNode('timeout')->min(0)->defaultValue(30000)->end()
                ->append($registry->component('exporter', MetricExporter::class)->isRequired())
                ->append($registry->componentList('producers', MetricProducer::class))
                ->arrayNode('cardinality_limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('default')->min(1)->defaultNull()->end()
                        ->integerNode('counter')->min(1)->defaultNull()->end()
                        ->integerNode('gauge')->min(1)->defaultNull()->end()
                        ->integerNode('histogram')->min(1)->defaultNull()->end()
                        ->integerNode('observable_counter')->min(1)->defaultNull()->end()
                        ->integerNode('observable_gauge')->min(1)->defaultNull()->end()
                        ->integerNode('observable_up_down_counter')->min(1)->defaultNull()->end()
                        ->integerNode('up_down_counter')->min(1)->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
