<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Metrics\Aggregation;
use Nevay\OTelSDK\Metrics\Aggregation\DropAggregation;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Aggregation>
 */
final class AggregationDrop implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): Aggregation {
        return new DropAggregation();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('drop');
    }
}
