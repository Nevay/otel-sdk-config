<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use InvalidArgumentException;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregation;
use Nevay\OTelSDK\Metrics\Aggregation;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

final class AggregationExplicitBucketHistogram implements ComponentProvider {

    /**
     * @param array{
     *     boundaries: list<float|int>,
     *     record_min_max: bool,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Aggregation {
        return new ExplicitBucketHistogramAggregation(
            boundaries: $properties['boundaries'] ?: null,
            recordMinMax: $properties['record_min_max'],
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('explicit_bucket_histogram');
        $node
            ->children()
                ->arrayNode('boundaries')
                    ->floatPrototype()->end()
                    ->validate()
                        ->ifArray()
                        ->then(static function(array $boundaries): array {
                            $last = -INF;
                            foreach ($boundaries as $boundary) {
                                if ($boundary <= $last) {
                                    throw new InvalidArgumentException('histogram boundaries must be strictly ascending');
                                }

                                $last = $boundary;
                            }

                            return $boundaries;
                        })
                    ->end()
                ->end()
                ->booleanNode('record_min_max')->defaultTrue()->end()
            ->end()
        ;

        return $node;
    }
}
