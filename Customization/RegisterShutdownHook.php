<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;
use function register_shutdown_function;

/**
 * @experimental
 */
final class RegisterShutdownHook implements Customization {

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        // no-op
    }

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            (new MultiProvider([
                $config->tracerProvider,
                $config->meterProvider,
                $config->loggerProvider,
            ]))->shutdown(...),
        );
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
