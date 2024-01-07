<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Metrics;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Metrics\Aggregation\SumAggregationResolver;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class AggregationResolverSum implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): AggregationResolver {
        return new SumAggregationResolver();
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('sum');
    }
}
