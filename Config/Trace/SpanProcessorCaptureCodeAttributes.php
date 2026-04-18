<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\SpanProcessor\SourceCodeAttributesCapturingSpanProcessor;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanProcessor>
 */
final class SpanProcessorCaptureCodeAttributes implements ComponentProvider {

    /**
     * @param array{
     *     capture_stacktrace: bool,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): SpanProcessor {
        return new SourceCodeAttributesCapturingSpanProcessor(
            captureStacktrace: $properties['capture_stacktrace'],
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('capture_code_attributes/development');
        $node
            ->children()
                ->booleanNode('capture_stacktrace')->defaultFalse()->end()
            ->end()
        ;

        return $node;
    }
}
