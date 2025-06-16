<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\ParentBasedSampler;
use Nevay\OTelSDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<Sampler>
 */
final class SamplerLoaderParentBasedTraceIdRatio implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): Sampler {
        return new ParentBasedSampler(new TraceIdRatioBasedSampler($env->numeric('OTEL_TRACES_SAMPLER_ARG', max: 1) ?? 1.));
    }

    public function name(): string {
        return 'parentbased_traceidratio';
    }
}
