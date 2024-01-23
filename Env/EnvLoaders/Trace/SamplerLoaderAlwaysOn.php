<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOnSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderAlwaysOn implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): Sampler {
        return new AlwaysOnSampler();
    }

    public function name(): string {
        return 'always_on';
    }
}
