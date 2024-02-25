<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Metrics;

use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Metrics\Aggregation\LastValueAggregationResolver;
use Nevay\OTelSDK\Metrics\AggregationResolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class AggregationResolverLastValue implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): AggregationResolver {
        return new LastValueAggregationResolver();
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('last_value');
    }
}
