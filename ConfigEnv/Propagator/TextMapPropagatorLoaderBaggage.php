<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigEnv\Propagator;

use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @implements EnvComponentLoader<TextMapPropagatorInterface>
 */
final class TextMapPropagatorLoaderBaggage implements EnvComponentLoader {

    public function load(EnvResolver $env, EnvComponentLoaderRegistry $registry, Context $context): TextMapPropagatorInterface {
        return BaggagePropagator::getInstance();
    }

    public function name(): string {
        return 'baggage';
    }
}
