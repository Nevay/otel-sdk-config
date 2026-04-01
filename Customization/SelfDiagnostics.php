<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\SelfDiagnostics\LoggerProvider;
use Nevay\OTelSDK\Configuration\SelfDiagnostics\MeterProvider;
use Nevay\OTelSDK\Configuration\SelfDiagnostics\TracerProvider;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;

/**
 * @experimental
 */
final class SelfDiagnostics implements Customization {

    public function __construct(
        private readonly Customization $customization,
    ) {}

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        $this->customization->onApiAvailable($this->selfDiagnostics($config), $context);
    }

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        $this->customization->onSdkAvailable($this->selfDiagnostics($config), $context);
    }

    public function customizeTracerProvider(TracerProviderBuilder $tracerProviderBuilder, Context $context): void {
        $this->customization->customizeTracerProvider($tracerProviderBuilder, $context);
    }

    public function customizeMeterProvider(MeterProviderBuilder $meterProviderBuilder, Context $context): void {
        $this->customization->customizeMeterProvider($meterProviderBuilder, $context);
    }

    public function customizeLoggerProvider(LoggerProviderBuilder $loggerProviderBuilder, Context $context): void {
        $this->customization->customizeLoggerProvider($loggerProviderBuilder, $context);
    }

    private function selfDiagnostics(ConfigurationResult $config): ConfigurationResult {
        return new ConfigurationResult(
            propagator: $config->propagator,
            responsePropagator: $config->responsePropagator,
            tracerProvider: new TracerProvider($config->tracerProvider),
            meterProvider: new MeterProvider($config->meterProvider),
            loggerProvider: new LoggerProvider($config->loggerProvider),
            configProperties: $config->configProperties,
            distributionProperties: $config->distributionProperties,
        );
    }
}
