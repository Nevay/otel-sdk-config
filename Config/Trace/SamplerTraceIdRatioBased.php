<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerTraceIdRatioBased implements ComponentProvider {

    /**
     * @param array{
     *     ratio: float,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new TraceIdRatioBasedSampler($properties['ratio']);
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('trace_id_ratio_based');
        $node
            ->children()
                ->floatNode('ratio')->min(0)->max(1)->isRequired()->end()
            ->end()
        ;

        return $node;
    }
}
