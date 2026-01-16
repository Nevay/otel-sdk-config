<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\ResponsePropagator;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use OpenTelemetry\Contrib\Propagation\ServerTiming\ServerTimingPropagator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<ResponsePropagatorInterface>
 */
#[PackageDependency('open-telemetry/opentelemetry-propagation-server-timing', '^0.2')]
final class ResponsePropagatorServerTiming implements ComponentProvider {

    public function createPlugin(array $properties, Context $context): ResponsePropagatorInterface {
        return ServerTimingPropagator::getInstance();
    }

    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition {
        return $builder->arrayNode('servertiming');
    }
}
