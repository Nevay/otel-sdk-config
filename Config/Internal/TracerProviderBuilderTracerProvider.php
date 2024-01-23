<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Internal;

use Nevay\OTelSDK\Configuration\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\SpanProcessor;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @internal
 */
final class TracerProviderBuilderTracerProvider implements ComponentProvider {

    /**
     * @param array{
     *     limits: array{
     *         attribute_value_length_limit: ?int<0, max>,
     *         attribute_count_limit: ?int<0, max>,
     *         event_count_limit: ?int<0, max>,
     *         link_count_limit: ?int<0, max>,
     *         event_attribute_count_limit: ?int<0, max>,
     *         link_attribute_count_limit: ?int<0, max>,
     *     },
     *     sampler: ?ComponentPlugin<Sampler>,
     *     processors: list<ComponentPlugin<SpanProcessor>>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): TracerProviderBuilder {
        $tracerProviderBuilder = new TracerProviderBuilder();

        $tracerProviderBuilder->setSpanAttributeLimits(
            $properties['limits']['attribute_count_limit'],
            $properties['limits']['attribute_count_limit'],
        );
        $tracerProviderBuilder->setEventCountLimit($properties['limits']['event_count_limit']);
        $tracerProviderBuilder->setLinkCountLimit($properties['limits']['link_count_limit']);
        $tracerProviderBuilder->setEventAttributeLimits($properties['limits']['event_attribute_count_limit']);
        $tracerProviderBuilder->setLinkAttributeLimits($properties['limits']['link_attribute_count_limit']);
        $tracerProviderBuilder->setSampler($properties['sampler']?->create($context));
        foreach ($properties['processors'] as $processor) {
            $tracerProviderBuilder->addSpanProcessor($processor->create($context));
        }

        return $tracerProviderBuilder;
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('tracer_provider');
        $node
            ->children()
                ->arrayNode('limits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('attribute_value_length_limit')->min(0)->defaultNull()->end()
                        ->integerNode('attribute_count_limit')->min(0)->defaultNull()->end()
                        ->integerNode('event_count_limit')->min(0)->defaultValue(128)->end()
                        ->integerNode('link_count_limit')->min(0)->defaultValue(128)->end()
                        ->integerNode('event_attribute_count_limit')->min(0)->defaultNull()->end()
                        ->integerNode('link_attribute_count_limit')->min(0)->defaultNull()->end()
                    ->end()
                ->end()
                ->append(ComponentPlugin::provider('sampler', Sampler::class, $registry))
                ->append(ComponentPlugin::providerList('processors', SpanProcessor::class, $registry))
            ->end()
        ;

        return $node;
    }
}
