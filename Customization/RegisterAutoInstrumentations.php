<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManager;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\NoopHookManager;
use Throwable;

/**
 * @experimental
 */
final class RegisterAutoInstrumentations implements Customization {

    private readonly iterable $instrumentations;
    private readonly HookManagerInterface $hookManager;

    public function __construct(?iterable $instrumentations = null, ?HookManagerInterface $hookManager = null) {
        $this->instrumentations = $instrumentations ?? ServiceLoader::load(Instrumentation::class);
        $this->hookManager = $hookManager ?? ServiceLoader::load(HookManagerInterface::class)->getIterator()->current() ?? new NoopHookManager();
    }

    public function onApiAvailable(ConfigurationResult $config, Context $context): void {
        $instrumentationContext = new AutoInstrumentation\Context(
            tracerProvider: $config->tracerProvider,
            meterProvider: $config->meterProvider,
            loggerProvider: $config->loggerProvider,
            propagator: $config->propagator,
            responsePropagator: $config->responsePropagator,
        );

        $scope = HookManager::enable()->activate();
        try {
            foreach ($this->instrumentations as $instrumentation) {
                $context->logger->info('Registering instrumentation', ['instrumentation' => $instrumentation::class]);
                try {
                    $instrumentation->register($this->hookManager, $config->configProperties, $instrumentationContext);
                } catch (Throwable $e) {
                    $context->logger->error('Error during instrumentation registration', ['exception' => $e, 'instrumentation' => $instrumentation]);
                }
            }
        } finally {
            $scope->detach();
        }
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
