<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Trace;

use Nevay\OTelSDK\Otlp\OtlpStreamSpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\SimpleSpanProcessor;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use function Amp\ByteStream\getStdout;

/**
 * @implements EnvComponentLoader<SpanProcessor>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class SpanProcessorLoaderConsole implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): SpanProcessor {
        return new SimpleSpanProcessor(
            spanExporter: new OtlpStreamSpanExporter(
                stream: getStdout(),
                logger: $context->logger,
            ),
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'console';
    }
}
