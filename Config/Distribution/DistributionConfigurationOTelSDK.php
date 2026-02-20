<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Distribution;

use Nevay\OTelSDK\Configuration\Distribution\DistributionConfiguration;
use Nevay\OTelSDK\Configuration\Distribution\OTelSDKConfiguration;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<DistributionConfiguration>
 */
final class DistributionConfigurationOTelSDK implements ComponentProvider {

    /**
     * @param array{
     *     shutdown_timeout: ?float,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): DistributionConfiguration {
        return new OTelSDKConfiguration(
            shutdownTimeout: $properties['shutdown_timeout'],
        );
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        $node = $builder->arrayNode('tbachert/otel-sdk');
        $node
            ->children()
                ->floatNode('shutdown_timeout')->min(0)->defaultNull()->end()
            ->end()
        ;

        return $node;
    }
}
