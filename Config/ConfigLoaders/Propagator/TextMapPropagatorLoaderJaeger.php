<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\Jaeger\JaegerPropagator;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderJaeger implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
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
