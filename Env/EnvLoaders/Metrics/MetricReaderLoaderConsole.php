<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OTelSDK\Otlp\OtlpStreamMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class MetricReaderLoaderConsole implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: new OtlpStreamMetricExporter(
                stream: getStdout(),
                logger: $context->logger,
            ),
            exportIntervalMillis: $env->numeric('OTEL_METRIC_EXPORT_INTERVAL') ?? 10000,
            exportTimeoutMillis: $env->numeric('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
            meterProvider: $context->meterProvider,
        );
    }

    public function name(): string {
        return 'console';
    }
}
