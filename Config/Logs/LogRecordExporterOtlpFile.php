<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Logs;

use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Otlp\OtlpStreamLogRecordExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function error_get_last;
use function fopen;

/**
 * @implements ComponentProvider<LogRecordExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class LogRecordExporterOtlpFile implements ComponentProvider {

    /**
     * @param array{
     *     output_stream: 'stdout'|'stderr'|string,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordExporter {
        return new OtlpStreamLogRecordExporter(
            stream: match ($properties['output_stream']) {
                'stdout' => getStdout(),
                'stderr' => getStderr(),
                default => new WritableResourceStream(@fopen($properties['output_stream'], 'ab') ?: throw new StreamException(error_get_last()['message'])),
            },
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('otlp_file/development');
        $node
            ->children()
                ->scalarNode('output_stream')
                    ->defaultValue('stdout')
                    ->validate()->always(Util::ensureString())->end()
                    ->validate()->ifNotInArray(['stdout', 'stderr'])->then(Util::ensurePath())->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
