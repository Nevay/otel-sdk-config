<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Propagator;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderBaggage implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return BaggagePropagator::getInstance();
    }

    public function type(): string {
        return TextMapPropagatorInterface::class;
    }

    public function name(): string {
        return 'baggage';
    }

    public function dependencies(): array {
        return [];
    }
}
