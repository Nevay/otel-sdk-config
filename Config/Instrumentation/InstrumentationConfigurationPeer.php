<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Instrumentation;

use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Configuration\Validation;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
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
                            ->scalarNode('peer')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                            ->scalarNode('service')->isRequired()->validate()->always(Util::ensureString())->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
