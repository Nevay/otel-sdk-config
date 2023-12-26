<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;

/**
 * @implements Loader<MetricReader>
 */
final class MetricReaderLoaderPeriodic implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: $registry->load(MetricExporter::class, $config['exporter'], $context),
            exportIntervalMillis: $config['interval'] ?? 60000,
            exportTimeoutMillis: $config['timeout'] ?? 30000,
            meterProvider: $context->meterProvider,
        );
    }

    public function type(): string {
        return MetricReader::class;
    }

    public function name(): string {
        return 'periodic';
    }

    public function dependencies(): array {
        return [];
    }
}
