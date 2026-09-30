<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Instrumentation;

use Nevay\OTelSDK\Configuration\Internal\Config\ArrayInstrumentationConfiguration;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<GeneralInstrumentationConfiguration>
 */
final class InstrumentationConfigurationCode implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): GeneralInstrumentationConfiguration {
        return new ArrayInstrumentationConfiguration($properties, 'code');
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('code');
        $node
            ->children()
                ->arrayNode('semconv')
                    ->children()
                        ->integerNode('version')->min(0)->defaultNull()->end()
                        ->booleanNode('experimental')->defaultFalse()->end()
                        ->booleanNode('dual_emit')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
