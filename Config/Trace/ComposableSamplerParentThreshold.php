<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableParentThresholdSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ComposableSampler>
 */
final class ComposableSamplerParentThreshold implements ComponentProvider {

    /**
     * @param array{
     *     root: ComponentPlugin<ComposableSampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ComposableSampler {
        return new ComposableParentThresholdSampler($properties['root']->create($context));
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('parent_threshold');
        $node
            ->children()
                ->append($registry->component('root', ComposableSampler::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
