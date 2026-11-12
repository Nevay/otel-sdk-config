<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Instrumentation;

use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;
use OpenTelemetry\API\Instrumentation\Configuration\General\HttpConfig;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<GeneralInstrumentationConfiguration>
 */
#[PackageDependency('open-telemetry/api', '<1.11')]
final class InstrumentationConfigurationHttp implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): GeneralInstrumentationConfiguration {
        return new HttpConfig($properties);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('http');
        $node
            ->children()
                ->arrayNode('semconv')
                    ->children()
                        ->integerNode('version')->min(0)->defaultNull()->end()
                        ->booleanNode('experimental')->defaultFalse()->end()
                        ->booleanNode('dual_emit')->defaultFalse()->end()
                    ->end()
                ->end()
                ->append($this->node('client', $builder))
                ->append($this->node('server', $builder))
            ->end()
        ;

        return $node;
    }

    private function node(string $name, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode($name);
        $node
            ->children()
                ->arrayNode('known_methods')
                    ->defaultNull()
                    ->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()
                ->end()
                ->arrayNode('request_captured_headers')
                    ->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()
                ->end()
                ->arrayNode('response_captured_headers')
                    ->scalarPrototype()->validate()->always(Util::ensureString())->end()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
