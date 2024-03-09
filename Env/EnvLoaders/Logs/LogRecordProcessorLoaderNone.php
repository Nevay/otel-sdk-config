<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Logs;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\NoopLogRecordProcessor;

/**
 * @implements Loader<LogRecordProcessor>
 */
final class LogRecordProcessorLoaderNone implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): LogRecordProcessor {
        return new NoopLogRecordProcessor();
    }

    public function name(): string {
        return 'none';
    }
}
