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
final class InstrumentationConfigurationHttp implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): GeneralInstrumentationConfiguration {
        return new PeerConfig($properties);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('http');
        $node
            ->children()
                ->append($this->capturedHeaders('client', $builder))
                ->append($this->capturedHeaders('server', $builder))
            ->end()
        ;

        return $node;
    }

    private function capturedHeaders(string $name, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode($name);
        $node
            ->children()
                ->arrayNode('request_captured_headers')
                    ->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()
                ->end()
                ->arrayNode('response_captured_headers')
                    ->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
