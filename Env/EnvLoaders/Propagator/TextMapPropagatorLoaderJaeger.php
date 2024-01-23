<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Propagator;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\Jaeger\JaegerPropagator;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
#[PackageDependency('open-telemetry/extension-propagator-jaeger', '^0.0.2')]
final class TextMapPropagatorLoaderJaeger implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return JaegerPropagator::getInstance();
    }

    public function name(): string {
        return 'jaeger';
    }
}
