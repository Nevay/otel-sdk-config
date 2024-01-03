<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\ExplicitBucketHistogramAggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolver;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderExplicitBucketHistogram implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        $boundaries = $config['boundaries'] ?? null;
        $recordMinMax = $config['record_min_max'] ?? true;

        return new ExplicitBucketHistogramAggregationResolver($boundaries, $recordMinMax);
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
