<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Trace;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use Nevay\OtelSDK\Trace\SpanExporter;
use Nevay\OtelSDK\Trace\SpanProcessor;
use Nevay\OtelSDK\Trace\SpanProcessor\BatchSpanProcessor;

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

    public function type(): string {
        return SpanProcessor::class;
    }

    public function name(): string {
        return 'batch';
    }

    public function dependencies(): array {
        return [];
    }
}
