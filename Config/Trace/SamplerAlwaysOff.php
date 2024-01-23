<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Trace;

use Nevay\OTelSDK\Configuration\Config\ComponentProvider;
use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Trace\Sampler;
use Nevay\OTelSDK\Trace\Sampler\AlwaysOffSampler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class SamplerAlwaysOff implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        return new AlwaysOffSampler();
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('always_off');
    }
}
