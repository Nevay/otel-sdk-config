<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Amp\CancelledException;
use Amp\TimeoutCancellation;
use Nevay\OTelSDK\Common\Provider;
use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\Distribution\OTelSDKConfiguration;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;
use Psr\Log\LoggerInterface;
use function register_shutdown_function;

/**
 * @experimental
 */
final class RegisterShutdownHook implements Customization {

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        // no-op
    }

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        $distribution = $config->distributionProperties->getDistributionConfiguration(OTelSDKConfiguration::class) ?? new OTelSDKConfiguration();

        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            static function(Provider $provider, ?float $timeout, LoggerInterface $logger): void {
                $cancellation = null;
                if ($timeout !== null) {
                    $cancellation = new TimeoutCancellation($timeout);
                }

                try {
                    $provider->shutdown($cancellation);
                } catch (CancelledException $e) {
                    $logger->error('OTel SDK shutdown timeout', ['exception' => $e]);
                }
            },
            new MultiProvider([
                $config->tracerProvider,
                $config->meterProvider,
                $config->loggerProvider,
            ]),
            $distribution->shutdownTimeout,
            $context->logger,
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
