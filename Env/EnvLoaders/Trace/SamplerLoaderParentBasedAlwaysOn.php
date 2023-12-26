<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OtelSDK\Trace\Sampler\ParentBasedSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderParentBasedAlwaysOn implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): Sampler {
        return new ParentBasedSampler(new AlwaysOnSampler());
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'parentbased_always_on';
    }

    public function dependencies(): array {
        return [];
    }
}
