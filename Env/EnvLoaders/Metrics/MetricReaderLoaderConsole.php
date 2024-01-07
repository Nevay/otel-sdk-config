<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Metrics\MetricReader;
use Nevay\OtelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OtelSDK\Otlp\OtlpStreamMetricExporter;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<MetricReader>
 */
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

    public function type(): string {
        return MetricReader::class;
    }

    public function name(): string {
        return 'console';
    }

    public function dependencies(): array {
        return [
            'tbachert/otel-sdk-otlpexporter' => '^0.1',
            'amphp/byte-stream' => '^2.0',
        ];
    }
}
