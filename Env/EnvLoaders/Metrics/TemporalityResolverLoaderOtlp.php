<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Metrics\TemporalityResolver;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;
use function strtolower;

/**
 * @implements Loader<TemporalityResolver>
 */
final class TemporalityResolverLoaderOtlp implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TemporalityResolver {
        return match (strtolower($env->string('OTEL_EXPORTER_OTLP_METRICS_TEMPORALITY_PREFERENCE') ?? 'cumulative')) {
            'cumulative' => TemporalityResolvers::Cumulative,
            'delta' => TemporalityResolvers::Delta,
            'lowmemory' => TemporalityResolvers::LowMemory,
        };
    }

    public function type(): string {
        return TemporalityResolver::class;
    }

    public function name(): string {
        return 'otlp';
    }

    public function dependencies(): array {
        return [];
    }
}
