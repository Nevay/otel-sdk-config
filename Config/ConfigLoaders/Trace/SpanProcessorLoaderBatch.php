<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Trace;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Trace\SpanExporter;
use Nevay\OtelSDK\Trace\SpanProcessor;
use Nevay\OtelSDK\Trace\SpanProcessor\BatchSpanProcessor;

/**
 * @implements Loader<SpanProcessor>
 */
final class SpanProcessorLoaderBatch implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): SpanProcessor {
        return new BatchSpanProcessor(
            spanExporter: $registry->load(SpanExporter::class, $config['exporter'], $context),
            maxQueueSize: $config['max_queue_size'] ?? 2048,
            scheduledDelayMillis: $config['schedule_delay'] ?? 5000,
            exportTimeoutMillis: $config['export_timeout'] ?? 30000,
            maxExportBatchSize: $config['max_export_batch_size'] ?? 512,
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
