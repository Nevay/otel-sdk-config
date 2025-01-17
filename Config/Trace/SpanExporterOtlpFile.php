<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function error_get_last;
use function fopen;

#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class SpanExporterOtlpFile implements ComponentProvider {

    /**
     * @param array{
     *     output_stream: 'stdout'|'stderr'|string,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanExporter {
        return new OtlpStreamSpanExporter(
            stream: match ($properties['output_stream']) {
                'stdout' => getStdout(),
                'stderr' => getStderr(),
                default => new WritableResourceStream(@fopen($properties['output_stream'], 'ab') ?: throw new StreamException(error_get_last()['message'])),
            },
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('otlp_file');
        $node
            ->children()
                ->scalarNode('output_stream')->defaultValue('stdout')->validate()->always(Validation::ensureString())->end()->end()
            ->end()
        ;

        return $node;
    }
}
