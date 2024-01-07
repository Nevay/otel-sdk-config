<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Nevay\OtelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class MetricReaderPull implements ComponentProvider {

    /**
     * @param array{
     *     exporter: ComponentPlugin<MetricExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: $properties['exporter']->create($context),
            meterProvider: $context->meterProvider,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('pull');
        $node
            ->children()
                ->append(ComponentPlugin::provider('exporter', MetricExporter::class, $registry))
            ->end()
        ;

        return $node;
    }
}
