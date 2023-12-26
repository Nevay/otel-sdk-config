<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env\EnvLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\Loader;
use Nevay\OtelSDK\Configuration\Env\LoaderRegistry;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\Jaeger\JaegerPropagator;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderJaeger implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return JaegerPropagator::getInstance();
    }

    public function type(): string {
        return TextMapPropagatorInterface::class;
    }

    public function name(): string {
        return 'jaeger';
    }

    public function dependencies(): array {
        return [
            'open-telemetry/extension-propagator-jaeger' => '^1.0',
        ];
    }
}
