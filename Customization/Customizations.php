<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;

/**
 * @experimental
 */
final class Customizations implements Customization {

    private readonly iterable $customizations;

    public function __construct(Customization ...$customizations) {
        $this->customizations = $customizations;
    }

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        foreach ($this->customizations as $customization) {
            $customization->onApiAvailable($config, $context);
        }
    }

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        foreach ($this->customizations as $customization) {
            $customization->onSdkAvailable($config, $context);
        }
    }

    public function customizeTracerProvider(TracerProviderBuilder $tracerProviderBuilder, Context $context): void {
        foreach ($this->customizations as $customization) {
            $customization->customizeTracerProvider($tracerProviderBuilder, $context);
        }
    }

    public function customizeMeterProvider(MeterProviderBuilder $meterProviderBuilder, Context $context): void {
        foreach ($this->customizations as $customization) {
            $customization->customizeMeterProvider($meterProviderBuilder, $context);
        }
    }

    public function customizeLoggerProvider(LoggerProviderBuilder $loggerProviderBuilder, Context $context): void {
        foreach ($this->customizations as $customization) {
            $customization->customizeLoggerProvider($loggerProviderBuilder, $context);
        }
    }
}
