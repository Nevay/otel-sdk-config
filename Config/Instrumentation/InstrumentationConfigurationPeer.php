<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Instrumentation;

use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Validation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;
use OpenTelemetry\API\Instrumentation\Configuration\General\PeerConfig;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<GeneralInstrumentationConfiguration>
 */
final class InstrumentationConfigurationPeer implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): GeneralInstrumentationConfiguration {
        return new PeerConfig($properties);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('peer');
        $node
            ->children()
                ->arrayNode('service_mapping')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('peer')->isRequired()->validate()->always(Validation::ensureString())->end()->end()
                            ->scalarNode('service')->isRequired()->validate()->always(Validation::ensureString())->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
