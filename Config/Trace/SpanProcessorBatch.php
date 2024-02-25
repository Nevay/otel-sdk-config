<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\BatchSpanProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class SpanProcessorBatch implements ComponentProvider {

    /**
     * @param array{
     *     schedule_delay: int<0, max>,
     *     export_timeout: int<0, max>,
     *     max_queue_size: int<0, max>,
     *     max_export_batch_size: int<0, max>,
     *     exporter: ComponentPlugin<SpanExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanProcessor {
        return new BatchSpanProcessor(
            spanExporter: $properties['exporter']->create($context),
            maxQueueSize: $properties['max_queue_size'],
            scheduledDelayMillis: $properties['schedule_delay'],
            exportTimeoutMillis: $properties['export_timeout'],
            maxExportBatchSize: $properties['max_export_batch_size'],
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('batch');
        $node
            ->children()
                ->integerNode('schedule_delay')->min(0)->defaultValue(5000)->end()
                ->integerNode('export_timeout')->min(0)->defaultValue(30000)->end()
                ->integerNode('max_queue_size')->min(0)->defaultValue(2048)->end()
                ->integerNode('max_export_batch_size')->min(0)->defaultValue(512)->end()
                ->append($registry->component('exporter', SpanExporter::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
