<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<SpanExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class SpanExporterLoaderConsole implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanExporter {
        return new OtlpStreamSpanExporter(
            stream: getStdout(),
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'console';
    }
}
