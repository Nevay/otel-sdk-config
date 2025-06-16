<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\SpanExporter;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanProcessor>
 */
final class SpanProcessorSimple implements ComponentProvider {

    /**
     * @param array{
     *     exporter: ComponentPlugin<SpanExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanProcessor {
        return new SimpleSpanProcessor(
            spanExporter: $properties['exporter']->create($context),
            tracerProvider: $context->tracerProvider,
            meterProvider: $context->meterProvider,
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('simple');
        $node
            ->children()
                ->append($registry->component('exporter', SpanExporter::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
