<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Metrics;

use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\NoopMetricReader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<MetricReader>
 */
final class MetricReaderLoaderNone implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): MetricReader {
        return new NoopMetricReader();
    }

    public function name(): string {
        return 'none';
    }
}
