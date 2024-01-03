<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\LastValueAggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolver;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderLastValue implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        return new LastValueAggregationResolver();
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
