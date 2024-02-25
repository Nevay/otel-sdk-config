<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Logs;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\BatchLogRecordProcessor;

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
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function name(): string {
        return 'batch';
    }
}
