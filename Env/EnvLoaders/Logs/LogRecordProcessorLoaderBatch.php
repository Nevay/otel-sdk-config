<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Logs;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Logs\LogRecordExporter;
use Nevay\OtelSDK\Logs\LogRecordProcessor;
use Nevay\OtelSDK\Logs\LogRecordProcessor\BatchLogRecordProcessor;

/**
 * @implements Loader<LogRecordProcessor>
 */
final class LogRecordProcessorLoaderBatch implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): LogRecordProcessor {
        return new BatchLogRecordProcessor(
            logRecordExporter: $registry->load(LogRecordExporter::class, $env->string('OTEL_LOGS_EXPORTER') ?? 'otlp', $env, $context),
            maxQueueSize: $env->numeric('OTEL_BLRP_MAX_QUEUE_SIZE') ?? 2048,
            scheduledDelayMillis: $env->numeric('OTEL_BLRP_SCHEDULE_DELAY') ?? 5000,
            exportTimeoutMillis: $env->numeric('OTEL_BLRP_EXPORT_TIMEOUT') ?? 30000,
            maxExportBatchSize: $env->numeric('OTEL_BLRP_MAX_EXPORT_BATCH_SIZE') ?? 512,
            meterProvider: $context->meterProvider,
        );
    }

    public function name(): string {
        return 'batch';
    }
}
