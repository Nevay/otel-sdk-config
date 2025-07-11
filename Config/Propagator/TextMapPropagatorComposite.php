<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Propagator;

use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<TextMapPropagatorInterface>
 */
final class TextMapPropagatorComposite implements ComponentProvider {

    /**
     * @param list<ComponentPlugin<TextMapPropagatorInterface>> $properties
     */
    public function createPlugin(array $properties, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach ($properties as $plugin) {
            $propagators[] = $plugin->create($context);
        }

        return new MultiTextMapPropagator($propagators);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $registry->componentNames('composite', TextMapPropagatorInterface::class);
    }
}
