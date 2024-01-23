<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env\EnvLoaders\Propagator;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Env\Loader;
use Nevay\OTelSDK\Configuration\Env\LoaderRegistry;
use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @implements Loader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderBaggage implements Loader {

    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return BaggagePropagator::getInstance();
    }

    public function name(): string {
        return 'baggage';
    }
}
