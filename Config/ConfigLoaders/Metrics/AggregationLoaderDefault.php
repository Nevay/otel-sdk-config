<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolvers;

/**
 * @implements Loader<AggregationResolver>
 */
final class AggregationLoaderDefault implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): AggregationResolver {
        return AggregationResolvers::Default;
    }

    public function type(): string {
        return AggregationResolver::class;
    }

    public function name(): string {
        return 'default';
    }

    public function dependencies(): array {
        return [];
    }
}
