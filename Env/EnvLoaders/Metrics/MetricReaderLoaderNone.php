<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\NoopMetricReader;

/**
 * @implements Loader<MetricReader>
 */
final class MetricReaderLoaderNone implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): MetricReader {
        return new NoopMetricReader();
    }

    public function name(): string {
        return 'none';
    }
}
