<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OtelSDK\Trace\SpanExporter;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<SpanExporter>
 */
final class SpanExporterLoaderConsole implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanExporter {
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
