<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Amp\CancelledException;
use Nevay\OTelSDK\Common\Internal\Export\Cancellations;
use Nevay\OTelSDK\Common\Provider;
use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\Distribution\DistributionProperties;
use Nevay\OTelSDK\Configuration\Distribution\OTelSDKConfiguration;
use OpenTelemetry\API\Configuration\Context;
use Psr\Log\LoggerInterface;
use function register_shutdown_function;

/**
 * @experimental
 */
final class RegisterShutdownHook extends AbstractCustomization implements Customization {

    public function onSdkAvailable(ConfigurationResult $config, Context $context): void {
        register_shutdown_function(
            static function(ConfigurationResult $config): void {
                $config->keepAliveHandles = [];
            },
            $config,
        );

        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            static function(Provider $provider, DistributionProperties $distributionProperties, LoggerInterface $logger): void {
                $distribution = $distributionProperties->getDistributionConfiguration(OTelSDKConfiguration::class) ?? new OTelSDKConfiguration();
                $cancellation = Cancellations::withTimeout($distribution->shutdownTimeout);

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
            $config->distributionProperties,
            $context->logger,
        );
    }
}
