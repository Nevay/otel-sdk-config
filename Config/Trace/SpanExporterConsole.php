<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function Amp\ByteStream\getStdout;

/**
 * @implements ComponentProvider<SpanExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class SpanExporterConsole implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
        return new OtlpStreamSpanExporter(
            stream: getStdout(),
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('console');
    }
}
