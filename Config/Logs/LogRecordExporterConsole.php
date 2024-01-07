<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Logs;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Logs\LogRecordExporter;
use Nevay\OtelSDK\Otlp\OtlpStreamLogRecordExporter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function Amp\ByteStream\getStdout;

#[ComponentProviderDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[ComponentProviderDependency('amphp/byte-stream', '^2.0')]
final class LogRecordExporterConsole implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordExporter {
        return new OtlpStreamLogRecordExporter(getStdout(), $context->logger);
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('console');
    }
}
