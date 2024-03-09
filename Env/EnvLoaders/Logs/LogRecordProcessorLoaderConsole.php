<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Logs;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\SimpleLogRecordProcessor;
use Nevay\OTelSDK\Otlp\OtlpStreamLogRecordExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<LogRecordProcessor>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class LogRecordProcessorLoaderConsole implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): LogRecordProcessor {
        return new SimpleLogRecordProcessor(
            logRecordExporter: new OtlpStreamLogRecordExporter(
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
