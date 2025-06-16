<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OTelSDK\Trace\Sampler\ParentBasedSampler;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<Sampler>
 */
final class SamplerLoaderParentBasedAlwaysOn implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): Sampler {
        return new ParentBasedSampler(new AlwaysOnSampler());
    }

    public function name(): string {
        return 'parentbased_always_on';
    }
}
