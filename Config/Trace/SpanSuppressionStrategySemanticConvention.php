<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\SpanSuppression\SemanticConventionSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Trace\SpanSuppression\SemanticConventionResolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanSuppressionStrategy>
 */
final class SpanSuppressionStrategySemanticConvention implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): SpanSuppressionStrategy {
        return new SemanticConventionSuppressionStrategy(ServiceLoader::load(SemanticConventionResolver::class));
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('semantic_convention');
    }
}
