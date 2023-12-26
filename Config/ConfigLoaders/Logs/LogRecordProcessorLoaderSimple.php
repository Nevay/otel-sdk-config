<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Logs\LogRecordExporter;
use Nevay\OtelSDK\Logs\LogRecordProcessor;
use Nevay\OtelSDK\Logs\LogRecordProcessor\SimpleLogRecordProcessor;

/**
 * @implements Loader<LogRecordProcessor>
 */
final class LogRecordProcessorLoaderSimple implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): LogRecordProcessor {
        return new SimpleLogRecordProcessor(
            logRecordExporter: $registry->load(LogRecordExporter::class, $config['exporter'], $context),
            meterProvider: $context->meterProvider,
        );
    }

    public function type(): string {
        return LogRecordProcessor::class;
    }

    public function name(): string {
        return 'simple';
    }

    public function dependencies(): array {
        return [];
    }
}
