<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Nevay\OtelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\MetricExporterConfiguration;
use Nevay\OtelSDK\Configuration\MetricReaderConfiguration;
use Nevay\OtelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class MetricReaderPeriodic implements ComponentProvider {

    /**
     * @param array{
     *     interval: int<0, max>,
     *     timeout: int<0, max>,
     *     exporter: ComponentPlugin<MetricExporterConfiguration>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricReaderConfiguration {
        $exporter = $properties['exporter']->create($context);

        $reader = new PeriodicExportingMetricReader(
            metricExporter: $exporter->metricExporter,
            exportIntervalMillis: $properties['interval'],
            exportTimeoutMillis: $properties['timeout'],
            meterProvider: $context->meterProvider,
        );

        return new MetricReaderConfiguration(
            $reader,
            $exporter->temporalityResolver,
            $exporter->aggregationResolver,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('periodic');
        $node
            ->children()
                ->integerNode('interval')->min(0)->defaultValue(5000)->end()
                ->integerNode('timeout')->min(0)->defaultValue(30000)->end()
                ->append(ComponentPlugin::provider('exporter', MetricExporterConfiguration::class, $registry))
            ->end()
        ;

        return $node;
    }
}
