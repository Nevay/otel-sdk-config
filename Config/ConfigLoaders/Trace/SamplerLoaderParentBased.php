<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOffSampler;
use Nevay\OtelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OtelSDK\Trace\Sampler\ParentBasedSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderParentBased implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): Sampler {
        return new ParentBasedSampler(
            root:                   $registry->load(Sampler::class, $config['root'], $context),
            remoteParentSampled:    $registry->loadNullable(Sampler::class, $config['remote_parent_sampled']     ?? null, $context) ?? new AlwaysOnSampler(),
            remoteParentNotSampled: $registry->loadNullable(Sampler::class, $config['remote_parent_not_sampled'] ?? null, $context) ?? new AlwaysOffSampler(),
            localParentSampled:     $registry->loadNullable(Sampler::class, $config['local_parent_sampled']      ?? null, $context) ?? new AlwaysOnSampler(),
            localParentNotSampled:  $registry->loadNullable(Sampler::class, $config['local_parent_not_sampled']  ?? null, $context) ?? new AlwaysOffSampler(),
        );
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'parent_based';
    }

    public function dependencies(): array {
        return [];
    }
}
