<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Metrics;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Otlp\OtlpStreamMetricExporter;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<MetricExporter>
 */
final class MetricExporterLoaderConsole implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): MetricExporter {
        return new OtlpStreamMetricExporter(getStdout(), $context->logger);
    }

    public function type(): string {
        return MetricExporter::class;
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
