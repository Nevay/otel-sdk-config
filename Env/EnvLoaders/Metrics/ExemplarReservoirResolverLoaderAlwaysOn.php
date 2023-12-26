<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Metrics\ExemplarReservoirResolver;
use Nevay\OtelSDK\Metrics\ExemplarReservoirResolvers;

/**
 * @implements Loader<ExemplarReservoirResolver>
 */
final class ExemplarReservoirResolverLoaderAlwaysOn implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): ExemplarReservoirResolver {
        return ExemplarReservoirResolvers::All;
    }

    public function type(): string {
        return ExemplarReservoirResolver::class;
    }

    public function name(): string {
        return 'always_on';
    }

    public function dependencies(): array {
        return [];
    }
}
