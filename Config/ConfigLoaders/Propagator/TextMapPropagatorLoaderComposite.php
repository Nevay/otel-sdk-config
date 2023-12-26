<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderComposite implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        $propagators = [];
        foreach ($config as $name) {
            $propagators[] = $registry->load(TextMapPropagatorInterface::class, [$name => []], $context);
        }

        return new MultiTextMapPropagator($propagators);
    }

    public function type(): string {
        return TextMapPropagatorInterface::class;
    }

    public function name(): string {
        return 'composite';
    }

    public function dependencies(): array {
        return [];
    }
}
