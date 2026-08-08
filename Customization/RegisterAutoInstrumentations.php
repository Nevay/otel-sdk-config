<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\Internal\HookCountingManager;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\Config\SDK\Configuration\Context;
use Throwable;

/**
 * @experimental
 */
final class RegisterAutoInstrumentations extends AbstractCustomization implements Customization {

    /**
     * @param iterable<Instrumentation> $instrumentations
     */
    public function __construct(
        private readonly iterable $instrumentations,
        private readonly HookManagerInterface $hookManager,
        private readonly bool $skipGlobalInstrumentations = false,
    ) {}

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        $instrumentationContext = new AutoInstrumentation\Context(
            tracerProvider: $config->tracerProvider,
            meterProvider: $config->meterProvider,
            loggerProvider: $config->loggerProvider,
            propagator: $config->propagator,
            responsePropagator: $config->responsePropagator,
        );

        foreach ($this->instrumentations as $instrumentation) {
            if ($this->skipGlobalInstrumentations && self::isGlobalInstrumentation($instrumentation, $config->configProperties)) {
                continue;
            }

            $context->logger->info('Registering instrumentation', ['instrumentation' => $instrumentation::class]);
            try {
                $instrumentation->register($this->hookManager, $config->configProperties, $instrumentationContext);
            } catch (Throwable $e) {
                $context->logger->error('Error during instrumentation registration', ['exception' => $e, 'instrumentation' => $instrumentation]);
            }
        }
    }

    private static function isGlobalInstrumentation(Instrumentation $instrumentation, ConfigProperties $configProperties): bool {
        $hookManager = new HookCountingManager();
        $instrumentation->register($hookManager, $configProperties, new AutoInstrumentation\Context());

        return !$hookManager->hooks;
    }
}
