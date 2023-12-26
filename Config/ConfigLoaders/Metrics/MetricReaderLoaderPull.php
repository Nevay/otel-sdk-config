<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\MetricReader\PullMetricReader;

/**
 * @implements Loader<MetricReader>
 */
final class MetricReaderLoaderPull implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): MetricReader {
        return new PullMetricReader(
            metricExporter: $registry->load(MetricExporter::class, $config['exporter'], $context),
            meterProvider: $context->meterProvider,
        );
    }

    public function type(): string {
        return MetricReader::class;
    }

    public function name(): string {
        return 'pull';
    }

    public function dependencies(): array {
        return [];
    }
}
