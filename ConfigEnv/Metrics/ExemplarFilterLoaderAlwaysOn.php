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
final class ExemplarFilterLoaderAlwaysOn implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): ExemplarFilter {
        return ExemplarFilter::AlwaysOn;
    }

    public function name(): string {
        return 'always_on';
    }
}
