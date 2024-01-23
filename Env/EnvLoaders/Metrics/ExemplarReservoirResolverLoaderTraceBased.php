<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\ExemplarReservoirResolver;
use Nevay\OTelSDK\Metrics\ExemplarReservoirResolvers;

/**
 * @implements Loader<ExemplarReservoirResolver>
 */
final class ExemplarReservoirResolverLoaderTraceBased implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): ExemplarReservoirResolver {
        return ExemplarReservoirResolvers::WithSampledTrace;
    }

    public function name(): string {
        return 'trace_based';
    }
}
