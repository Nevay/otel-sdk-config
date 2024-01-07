<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\MetricExporterConfiguration;
use Nevay\OtelSDK\Metrics\AggregationResolvers;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;
use Nevay\OtelSDK\Otlp\OtlpStreamMetricExporter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function Amp\ByteStream\getStdout;

#[ComponentProviderDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[ComponentProviderDependency('amphp/byte-stream', '^2.0')]
final class MetricExporterConsole implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporterConfiguration {
        $exporter = new OtlpStreamMetricExporter(getStdout(), $context->logger);

        return new MetricExporterConfiguration(
            $exporter,
            TemporalityResolvers::LowMemory,
            AggregationResolvers::Default,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('console');
    }
}
