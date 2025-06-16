<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Propagator;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\Jaeger\JaegerPropagator;

/**
 * @implements EnvComponentLoader<TextMapPropagatorInterface>
 */
#[PackageDependency('open-telemetry/extension-propagator-jaeger', '^0.0.2')]
final class TextMapPropagatorLoaderJaeger implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return JaegerPropagator::getInstance();
    }

    public function name(): string {
        return 'jaeger';
    }
}
