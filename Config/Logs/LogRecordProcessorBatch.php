<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Logs;

use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\BatchLogRecordProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LogRecordProcessorBatch implements ComponentProvider {

    /**
     * @param array{
     *     schedule_delay: int<0, max>,
     *     export_timeout: int<0, max>,
     *     max_queue_size: int<0, max>,
     *     max_export_batch_size: int<0, max>,
     *     exporter: ComponentPlugin<LogRecordExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordProcessor {
        return new BatchLogRecordProcessor(
            logRecordExporter: $properties['exporter']->create($context),
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
                ->append($registry->component('exporter', LogRecordExporter::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
