<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\SimpleSpanProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class SpanProcessorSimple implements ComponentProvider {

    /**
     * @param array{
     *     exporter: ComponentPlugin<SpanExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanProcessor {
        return new SimpleSpanProcessor(
            spanExporter: $properties['exporter']->create($context),
            meterProvider: $context->meterProvider,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('simple');
        $node
            ->children()
                ->append(ComponentPlugin::provider('exporter', SpanExporter::class, $registry)->isRequired())
            ->end()
        ;

        return $node;
    }
}
