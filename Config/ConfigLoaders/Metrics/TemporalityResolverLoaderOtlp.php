<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\TemporalityResolver;
use Nevay\OtelSDK\Metrics\TemporalityResolvers;
use function strtolower;

/**
 * @implements Loader<TemporalityResolver>
 */
final class TemporalityResolverLoaderOtlp implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): TemporalityResolver {
        return match (strtolower($config['temporality_preference'] ?? 'cumulative')) {
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
