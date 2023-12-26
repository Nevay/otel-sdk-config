<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OtelSDK\Trace\SpanExporter;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<SpanExporter>
 */
final class SpanExporterLoaderConsole implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): SpanExporter {
        return new OtlpStreamSpanExporter(getStdout(), $context->logger);
    }

    public function type(): string {
        return SpanExporter::class;
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
