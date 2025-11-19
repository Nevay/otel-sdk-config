<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableProbabilitySampler;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ComposableSampler>
 */
final class ComposableSamplerProbability implements ComponentProvider {

    /**
     * @param array{
     *     ratio: float,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): ComposableSampler {
        return new ComposableProbabilitySampler($properties['ratio']);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('probability');
        $node
            ->children()
                ->floatNode('ratio')->min(0)->max(1)->isRequired()->end()
            ->end()
        ;

        return $node;
    }
}
