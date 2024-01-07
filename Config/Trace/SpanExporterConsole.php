<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Trace;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OtelSDK\Trace\SpanExporter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use function Amp\ByteStream\getStdout;

#[ComponentProviderDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[ComponentProviderDependency('amphp/byte-stream', '^2.0')]
final class SpanExporterConsole implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
        return new OtlpStreamSpanExporter(getStdout(), $context->logger);
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('console');
    }
}
