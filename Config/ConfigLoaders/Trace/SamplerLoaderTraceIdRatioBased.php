<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Trace\Sampler;
use Nevay\OtelSDK\Trace\Sampler\TraceIdRatioBasedSampler;

/**
 * @implements Loader<Sampler>
 */
final class SamplerLoaderTraceIdRatioBased implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): Sampler {
        return new TraceIdRatioBasedSampler($config['ratio']);
    }

    public function type(): string {
        return Sampler::class;
    }

    public function name(): string {
        return 'trace_id_ratio_based';
    }

    public function dependencies(): array {
        return [];
    }
}
