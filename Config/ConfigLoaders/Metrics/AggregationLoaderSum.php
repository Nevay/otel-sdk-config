<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\SumAggregation;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolvers;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderSum implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        return AggregationResolvers::resolved(new SumAggregation());
    }

    public function type(): string {
        return AggregationResolver::class;
    }

    public function name(): string {
        return 'sum';
    }

    public function dependencies(): array {
        return [];
    }
}
