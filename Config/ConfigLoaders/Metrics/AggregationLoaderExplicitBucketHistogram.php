<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation;
use Nevay\OtelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregation;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolvers;
use Nevay\OtelSDK\Metrics\InstrumentType;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderExplicitBucketHistogram implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        $boundaries = $config['boundaries'] ?? null;
        $recordMinMax = $config['record_min_max'] ?? null;
        return AggregationResolvers::callback(static function(InstrumentType $instrumentType, array $advisory) use ($boundaries, $recordMinMax): Aggregation {
            return new ExplicitBucketHistogramAggregation(
                boundaries: $boundaries ?? $advisory['ExplicitBucketBoundaries'] ?? [0, 5, 10, 25, 50, 75, 100, 250, 500, 1000, 2500, 5000, 7500, 10000],
                recordMinMax: $recordMinMax ?? true,
            );
        });
    }

    public function type(): string {
        return AggregationResolver::class;
    }

    public function name(): string {
        return 'explicit_bucket_histogram';
    }

    public function dependencies(): array {
        return [];
    }
}
