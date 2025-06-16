<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Metrics;

use Nevay\OTelSDK\Metrics\MetricReader;
use Nevay\OTelSDK\Metrics\MetricReader\PeriodicExportingMetricReader;
use Nevay\OTelSDK\Otlp\OtlpStreamMetricExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use function Amp\ByteStream\getStdout;

/**
 * @implements EnvComponentLoader<MetricReader>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class MetricReaderLoaderConsole implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): MetricReader {
        return new PeriodicExportingMetricReader(
            metricExporter: new OtlpStreamMetricExporter(
                stream: getStdout(),
                logger: $context->logger,
            ),
            exportIntervalMillis: $env->int('OTEL_METRIC_EXPORT_INTERVAL') ?? 10000,
            exportTimeoutMillis: $env->int('OTEL_METRIC_EXPORT_TIMEOUT') ?? 30000,
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'console';
    }
}
