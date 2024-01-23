<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOffSampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOnSampler;
use Nevay\OTelSDK\Trace\Sampler\ParentBasedSampler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('parent_based');
        $node
            ->children()
                ->append(ComponentPlugin::provider('root', Sampler::class, $registry)->isRequired())
                ->append(ComponentPlugin::provider('remote_parent_sampled', Sampler::class, $registry))
                ->append(ComponentPlugin::provider('remote_parent_not_sampled', Sampler::class, $registry))
                ->append(ComponentPlugin::provider('local_parent_sampled', Sampler::class, $registry))
                ->append(ComponentPlugin::provider('local_parent_not_sampled', Sampler::class, $registry))
            ->end()
        ;

        return $node;
    }
}
