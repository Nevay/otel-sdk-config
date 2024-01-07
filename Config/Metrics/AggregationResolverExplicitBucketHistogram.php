<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class AggregationResolverExplicitBucketHistogram implements ComponentProvider {

    /**
     * @param array{
     *     boundaries: list<float|int>,
     *     record_min_max: bool,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): AggregationResolver {
        return new ExplicitBucketHistogramAggregationResolver(
            boundaries: $properties['boundaries'] ?: null,
            recordMinMax: $properties['record_min_max'],
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('explicit_bucket_histogram');
        $node
            ->children()
                ->arrayNode('boundaries')
                    ->floatPrototype()->end()
                ->end()
                ->booleanNode('record_min_max')->defaultTrue()->end()
            ->end()
        ;

        return $node;
    }
}
