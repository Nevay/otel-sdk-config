<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\Extension\Propagator\B3\B3Propagator;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderB3Multi implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return B3Propagator::getB3MultiHeaderInstance();
    }

    public function type(): string {
        return TextMapPropagatorInterface::class;
    }

    public function name(): string {
        return 'b3multi';
    }

    public function dependencies(): array {
        return [
            'open-telemetry/extension-propagator-b3' => '^1.0',
        ];
    }
}
