<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Logs;

use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\NoopLogRecordProcessor;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;

/**
 * @implements EnvComponentLoader<LogRecordProcessor>
 */
final class LogRecordProcessorLoaderNone implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): LogRecordProcessor {
        return new NoopLogRecordProcessor();
    }

    public function name(): string {
        return 'none';
    }
}
