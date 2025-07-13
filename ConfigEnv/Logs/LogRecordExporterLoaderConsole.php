<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Logs;

use Nevay\OTelSDK\Configuration\ConfigEnv\Attributes\AssociateWithSimpleLogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Otlp\OtlpStreamLogRecordExporter;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use function Amp\ByteStream\getStdout;

/**
 * @implements EnvComponentLoader<LogRecordExporter>
 */
#[PackageDependency('tbachert/otel-sdk-otlpexporter', '^0.1')]
#[PackageDependency('amphp/byte-stream', '^2.0')]
#[AssociateWithSimpleLogRecordProcessor]
final class LogRecordExporterLoaderConsole implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): LogRecordExporter {
        return new OtlpStreamLogRecordExporter(
            stream: getStdout(),
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'console';
    }
}
