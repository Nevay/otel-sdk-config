<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Propagator;

use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\Jaeger\JaegerPropagator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

#[ComponentProviderDependency('open-telemetry/extension-propagator-jaeger', '^0.0.2')]
final class TextMapPropagatorJaeger implements ComponentProvider {

    /**
     * @param array{} $properties
     */
    public function createPlugin(array $properties, Context $context): TextMapPropagatorInterface {
        return JaegerPropagator::getInstance();
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        return new ArrayNodeDefinition('jaeger');
    }
}
