<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Metrics\TemporalityResolver;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;

/**
 * @implements Loader<TemporalityResolver>
 */
final class TemporalityResolverLoaderPrometheus implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TemporalityResolver {
        return TemporalityResolvers::Cumulative;
    }

    public function type(): string {
        return TemporalityResolver::class;
    }

    public function name(): string {
        return 'prometheus';
    }

    public function dependencies(): array {
        return [];
    }
}
