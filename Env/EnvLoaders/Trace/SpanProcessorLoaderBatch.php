<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\BatchSpanProcessor;

/**
 * @implements Loader<SpanProcessor>
 */
final class SpanProcessorLoaderBatch implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): SpanProcessor {
        return new BatchSpanProcessor(
            spanExporter: $registry->load(SpanExporter::class, $env->string('OTEL_TRACES_EXPORTER') ?? 'otlp', $env, $context),
            maxQueueSize: $env->numeric('OTEL_BSP_MAX_QUEUE_SIZE') ?? 2048,
            scheduledDelayMillis: $env->numeric('OTEL_BSP_SCHEDULE_DELAY') ?? 5000,
            exportTimeoutMillis: $env->numeric('OTEL_BSP_EXPORT_TIMEOUT') ?? 30000,
            maxExportBatchSize: $env->numeric('OTEL_BSP_MAX_EXPORT_BATCH_SIZE') ?? 512,
            meterProvider: $context->meterProvider,
        );
    }

    public function name(): string {
        return 'batch';
    }
}
