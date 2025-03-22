<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Resource;

use Nevay\OTelSDK\Common\ResourceDetector;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResourceDetector>
 */
final class ResourceDetectorProcess implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): ResourceDetector {
        return new ResourceDetector\Process();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('process');
    }
}
