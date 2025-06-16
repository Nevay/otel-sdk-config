<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Logs;

use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Otlp\OtlpStreamLogRecordExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function Amp\ByteStream\getStdout;

/**
 * @implements ComponentProvider<LogRecordExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class LogRecordExporterConsole implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordExporter {
        return new OtlpStreamLogRecordExporter(
            stream: getStdout(),
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('console');
    }
}
