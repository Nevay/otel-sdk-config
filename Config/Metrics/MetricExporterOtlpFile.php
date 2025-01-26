<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use Nevay\OTelSDK\Metrics\Aggregation\Base2ExponentialBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\DefaultAggregation;
use Nevay\OTelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\InstrumentType;
use Nevay\OTelSDK\Metrics\MetricExporter;
use Nevay\OTelSDK\Metrics\TemporalityResolvers;
use Nevay\OTelSDK\Otlp\OtlpStreamMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function error_get_last;
use function fopen;

#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class MetricExporterOtlpFile implements ComponentProvider {

    /**
     * @param array{
     *     output_stream: 'stdout'|'stderr'|string,
     *     temporality_preference: 'cumulative'|'delta'|'lowmemory',
     *     default_histogram_aggregation: 'explicit_bucket_histogram'|'base2_exponential_bucket_histogram',
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): MetricExporter {
        return new OtlpStreamMetricExporter(
            stream: match ($properties['output_stream']) {
                'stdout' => getStdout(),
                'stderr' => getStderr(),
                default => new WritableResourceStream(@fopen($properties['output_stream'], 'ab') ?: throw new StreamException(error_get_last()['message'])),
            },
            temporalityResolver: match ($properties['temporality_preference']) {
                'cumulative' => TemporalityResolvers::Cumulative,
                'delta' => TemporalityResolvers::Delta,
                'lowmemory' => TemporalityResolvers::LowMemory,
            },
            aggregation: (new DefaultAggregation())->with(InstrumentType::Histogram, match ($properties['default_histogram_aggregation']) {
                'explicit_bucket_histogram' => new ExplicitBucketHistogramAggregation(),
                'base2_exponential_bucket_histogram' => new Base2ExponentialBucketHistogramAggregation(),
            }),
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('otlp_file');
        $node
            ->children()
                ->scalarNode('output_stream')->defaultValue('stdout')->validate()->always(Validation::ensureString())->end()->end()
                ->enumNode('temporality_preference')
                    ->values(['cumulative', 'delta', 'lowmemory'])
                    ->defaultValue('cumulative')
                ->end()
                ->enumNode('default_histogram_aggregation')
                    ->values(['explicit_bucket_histogram', 'base2_exponential_bucket_histogram'])
                    ->defaultValue('explicit_bucket_histogram')
                ->end()
            ->end()
        ;

        return $node;
    }
}
