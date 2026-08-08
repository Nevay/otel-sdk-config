<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;

/**
 * @experimental
 */
final class RegisterGlobals extends AbstractCustomization implements Customization {

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        Globals::registerInitializer(static fn(Configurator $configurator): Configurator => $configurator
            ->withPropagator($config->propagator)
            ->withResponsePropagator($config->responsePropagator)
            ->withTracerProvider($config->tracerProvider)
            ->withMeterProvider($config->meterProvider)
            ->withLoggerProvider($config->loggerProvider)
        );
    }
}
