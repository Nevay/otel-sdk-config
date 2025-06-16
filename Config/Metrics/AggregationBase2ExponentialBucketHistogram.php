<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Metrics\Aggregation;
use Nevay\OTelSDK\Metrics\Aggregation\Base2ExponentialBucketHistogramAggregation;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Aggregation>
 */
final class AggregationBase2ExponentialBucketHistogram implements ComponentProvider {

    /**
     * @param array{
     *     max_scale: int<-10,20>,
     *     max_size: int<2, max>,
     *     record_min_max: bool,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Aggregation {
        return new Base2ExponentialBucketHistogramAggregation(
            maxSize: $properties['max_size'],
            maxScale: $properties['max_scale'],
            recordMinMax: $properties['record_min_max'],
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('base2_exponential_bucket_histogram');
        $node
            ->children()
                ->integerNode('max_scale')->min(-10)->max(20)->defaultValue(20)->end()
                ->integerNode('max_size')->min(2)->defaultValue(160)->end()
                ->booleanNode('record_min_max')->defaultTrue()->end()
            ->end()
        ;

        return $node;
    }
}
