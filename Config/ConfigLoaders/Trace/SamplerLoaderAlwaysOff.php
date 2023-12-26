<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOffSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderAlwaysOff implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): Sampler {
        return new AlwaysOffSampler();
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'always_off';
    }

    public function dependencies(): array {
        return [];
    }
}
