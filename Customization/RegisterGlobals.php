<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;

/**
 * @experimental
 */
final class RegisterGlobals implements Customization {

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        Globals::registerInitializer(static fn(Configurator $configurator): Configurator => $configurator
            ->withPropagator($config->propagator)
            ->withResponsePropagator($config->responsePropagator)
            ->withTracerProvider($config->tracerProvider)
            ->withMeterProvider($config->meterProvider)
            ->withLoggerProvider($config->loggerProvider)
        );
    }

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        // no-op
    }

    public function customizeTracerProvider(TracerProviderBuilder $tracerProviderBuilder, Context $context): void {
        // no-op
    }

    public function customizeMeterProvider(MeterProviderBuilder $meterProviderBuilder, Context $context): void {
        // no-op
    }

    public function customizeLoggerProvider(LoggerProviderBuilder $loggerProviderBuilder, Context $context): void {
        // no-op
    }
}
