<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Logs;

use Nevay\OTelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Logs\LogRecordExporter;
use Nevay\OTelSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Logs\LogRecordProcessor\SimpleLogRecordProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LogRecordProcessorSimple implements ComponentProvider {

    /**
     * @param array{
     *     exporter: ComponentPlugin<LogRecordExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordProcessor {
        return new SimpleLogRecordProcessor(
            logRecordExporter: $properties['exporter']->create($context),
            meterProvider: $context->meterProvider,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('simple');
        $node
            ->children()
                ->append(ComponentPlugin::provider('exporter', LogRecordExporter::class, $registry)->isRequired())
            ->end()
        ;

        return $node;
    }
}
