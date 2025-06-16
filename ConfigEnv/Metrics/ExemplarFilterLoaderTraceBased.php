<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Metrics;

use Nevay\OTelSDK\Metrics\ExemplarFilter;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<ExemplarFilter>
 */
final class ExemplarFilterLoaderTraceBased implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): ExemplarFilter {
        return ExemplarFilter::TraceBased;
    }

    public function name(): string {
        return 'trace_based';
    }
}
