<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Resource;

use Nevay\OTelSDK\Common\ResourceDetector;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResourceDetector>
 */
final class ResourceDetectorService implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): ResourceDetector {
        return new ResourceDetector\Service();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('service');
    }
}
