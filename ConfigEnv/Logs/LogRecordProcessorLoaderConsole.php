<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Logs;

use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\SimpleLogRecordProcessor;
use Nevay\OTelSDK\Otlp\OtlpStreamLogRecordExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use function Amp\ByteStream\getStdout;

/**
 * @implements EnvComponentLoader<LogRecordProcessor>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
final class LogRecordProcessorLoaderConsole implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): LogRecordProcessor {
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
