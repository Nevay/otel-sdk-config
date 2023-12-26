<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOnSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderAlwaysOn implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): Sampler {
        return new AlwaysOnSampler();
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'always_on';
    }

    public function dependencies(): array {
        return [];
    }
}
