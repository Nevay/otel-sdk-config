<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\MetricProducer;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function array_map;

final class MetricReaderPeriodic implements ComponentProvider {

    /**
     * @param array{
     *     interval: int<0, max>,
     *     timeout: int<0, max>,
     *     exporter: ComponentPlugin<MetricExporter>,
     *     producers: list<ComponentPlugin<MetricProducer>>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: $properties['exporter']->create($context),
            exportIntervalMillis: $properties['interval'],
            exportTimeoutMillis: $properties['timeout'],
            metricProducers: array_map(static fn(ComponentPlugin $producer) => $producer->create($context), $properties['producers']),
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('periodic');
        $node
            ->children()
            ->integerNode('interval')->min(0)->defaultValue(5000)->end()
            ->integerNode('timeout')->min(0)->defaultValue(30000)->end()
            ->append($registry->component('exporter', MetricExporter::class)->isRequired())
            ->append($registry->componentList('producers', MetricProducer::class))
            ->end()
        ;

        return $node;
    }
}
