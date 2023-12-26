<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\TraceIdRatioBasedSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderTraceIdRatio implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): Sampler {
        return new TraceIdRatioBasedSampler($env->numeric('OTEL_TRACES_SAMPLER_ARG') ?? 1.);
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'traceidratio';
    }

    public function dependencies(): array {
        return [];
    }
}
