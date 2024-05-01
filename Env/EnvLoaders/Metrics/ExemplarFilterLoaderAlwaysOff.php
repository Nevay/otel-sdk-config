<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\ExemplarFilter;

/**
 * @implements Loader<ExemplarFilter>
 */
final class ExemplarFilterLoaderAlwaysOff implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): ExemplarFilter {
        return ExemplarFilter::AlwaysOff;
    }

    public function name(): string {
        return 'always_off';
    }
}
