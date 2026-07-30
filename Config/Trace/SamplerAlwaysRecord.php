<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysRecordSampler;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerAlwaysRecord implements ComponentProvider {

    /**
     * @param array{
     *     root: ComponentPlugin<Sampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new AlwaysRecordSampler(
            delegate: $properties['root']->create($context),
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('always_record');
        $node
            ->children()
                ->append($registry->component('root', Sampler::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
