<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\OTelSDK\Jaeger\ComposableJaegerRemoteSampler;
use Nevay\OTelSDK\Jaeger\GrpcSamplingManager;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableSampler;
use Nevay\OTelSDK\Trace\Sampler\Composable\ComposableProbabilitySampler;
use Nevay\OTelSDK\Trace\Sampler\CompositeSampler;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<Sampler>
 */
#[PackageDependency('tbachert/otel-sdk-jaegerremotesampler', '^0.1')]
final class SamplerJaegerRemote implements ComponentProvider {

    /**
     * @param array{
     *     endpoint: string,
     *     interval: int<0, max>,
     *     initial_sampler: ?ComponentPlugin<ComposableSampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new CompositeSampler(
            sampler: new ComposableJaegerRemoteSampler(
                serviceName: '',
                initialSampler: $properties['initial_sampler']?->create($context) ?? new ComposableProbabilitySampler(0.001),
                samplingManager: new GrpcSamplingManager($properties['endpoint']),
                pollingIntervalMillis: $properties['interval'],
                logger: $context->logger,
            ),
            logger: $context->logger,
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('jaeger_remote');
        $node
            ->children()
                ->scalarNode('endpoint')->defaultValue('http://localhost:5779')->validate()->always(Util::ensureString())->end()->end()
                ->integerNode('interval')->defaultValue(60000)->min(0)->end()
                ->append($registry->component('initial_sampler', ComposableSampler::class))
            ->end()
        ;

        return $node;
    }
}
