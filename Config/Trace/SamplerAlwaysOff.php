<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerAlwaysOff implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new AlwaysOffSampler();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('always_off');
    }
}
