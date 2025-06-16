<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Propagator;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\B3\B3Propagator;

/**
 * @implements EnvComponentLoader<TextMapPropagatorInterface>
 */
#[PackageDependency('open-telemetry/extension-propagator-b3', '^1.0.1')]
final class TextMapPropagatorLoaderB3Multi implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return B3Propagator::getB3MultiHeaderInstance();
    }

    public function name(): string {
        return 'b3multi';
    }
}
