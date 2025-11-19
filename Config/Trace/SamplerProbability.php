<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableProbabilitySampler;
use Nevay\OTelSDK\Trace\Sampler\CompositeSampler;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerProbability implements ComponentProvider {

    /**
     * @param array{
     *     ratio: float,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new CompositeSampler(
            sampler: new ComposableProbabilitySampler($properties['ratio']),
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('probability/development');
        $node
            ->children()
                ->floatNode('ratio')->min(0)->max(1)->isRequired()->end()
            ->end()
        ;

        return $node;
    }
}
