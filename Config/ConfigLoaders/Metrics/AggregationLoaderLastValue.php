<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\LastValueAggregation;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolvers;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderLastValue implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        return AggregationResolvers::resolved(new LastValueAggregation());
    }

    public function type(): string {
        return AggregationResolver::class;
    }

    public function name(): string {
        return 'last_value';
    }

    public function dependencies(): array {
        return [];
    }
}
