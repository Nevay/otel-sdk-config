<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;

/**
 * @experimental
 */
interface Customization {

    public function onApiAvailable(ConfigurationResult $config, Context $context): void;

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void;

    public function customizeTracerProvider(TracerProviderBuilder $tracerProviderBuilder, Context $context): void;

    public function customizeMeterProvider(MeterProviderBuilder $meterProviderBuilder, Context $context): void;

    public function customizeLoggerProvider(LoggerProviderBuilder $loggerProviderBuilder, Context $context): void;
}
