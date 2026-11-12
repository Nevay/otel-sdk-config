<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Propagator;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<TextMapPropagatorInterface>
 */
#[PackageDependency('open-telemetry/api', '<1.11')]
final class TextMapPropagatorBaggage implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): TextMapPropagatorInterface {
        return BaggagePropagator::getInstance();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('baggage');
    }
}
