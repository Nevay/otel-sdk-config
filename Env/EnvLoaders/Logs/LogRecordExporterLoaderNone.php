<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Logs;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Logs\LogRecordExporter\NoopLogRecordExporter;

/**
 * @implements Loader<LogRecordExporter>
 */
final class LogRecordExporterLoaderNone implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): LogRecordExporter {
        return new NoopLogRecordExporter();
    }

    public function name(): string {
        return 'none';
    }
}
