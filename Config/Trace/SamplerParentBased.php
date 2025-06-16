<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOffSampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OTelSDK\Trace\Sampler\ParentBasedSampler;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerParentBased implements ComponentProvider {

    /**
     * @param array{
     *     root: ComponentPlugin<Sampler>,
     *     remote_parent_sampled: ?ComponentPlugin<Sampler>,
     *     remote_parent_not_sampled: ?ComponentPlugin<Sampler>,
     *     local_parent_sampled: ?ComponentPlugin<Sampler>,
     *     local_parent_not_sampled: ?ComponentPlugin<Sampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new ParentBasedSampler(
            root: $properties['root']->create($context),
            remoteParentSampled: $properties['remote_parent_sampled']?->create($context) ?? new AlwaysOnSampler(),
            remoteParentNotSampled: $properties['remote_parent_not_sampled']?->create($context) ?? new AlwaysOffSampler(),
            localParentSampled: $properties['local_parent_sampled']?->create($context) ?? new AlwaysOnSampler(),
            localParentNotSampled: $properties['local_parent_not_sampled']?->create($context) ?? new AlwaysOffSampler(),
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('parent_based');
        $node
            ->children()
                ->append($registry->component('root', Sampler::class)->isRequired())
                ->append($registry->component('remote_parent_sampled', Sampler::class))
                ->append($registry->component('remote_parent_not_sampled', Sampler::class))
                ->append($registry->component('local_parent_sampled', Sampler::class))
                ->append($registry->component('local_parent_not_sampled', Sampler::class))
            ->end()
        ;

        return $node;
    }
}
