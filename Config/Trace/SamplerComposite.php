<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use Nevay\OTelSDK\Trace\Sampler\CompositeSampler;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
final class SamplerComposite implements ComponentProvider {

    /**
     * @param array{
     *     sampler: ComponentPlugin<ComposableSampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new CompositeSampler(
            sampler: $properties['sampler']->create($context),
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $registry->component('composite/development', ComposableSampler::class)
            ->validate()->always(static fn(ComponentPlugin $sampler): array => ['sampler' => $sampler])->end();
    }
}
