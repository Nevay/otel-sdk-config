<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Resource;

use Nevay\OTelSDK\Common\ResourceDetector;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResourceDetector>
 */
#[PackageDependency('tbachert/otel-sdk-resourcedetectors', '^0.1')]
final class ResourceDetectorContainer implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): ResourceDetector {
        return new ResourceDetector\Container();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('container');
    }
}
