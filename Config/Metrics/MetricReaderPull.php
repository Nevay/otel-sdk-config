<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
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
