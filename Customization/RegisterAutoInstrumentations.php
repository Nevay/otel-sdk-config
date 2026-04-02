<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Customization;

use Nevay\OTelSDK\Configuration\Customization;
use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use ReflectionParameter;
use Throwable;

/**
 * @experimental
 */
final class RegisterAutoInstrumentations implements Customization {

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
            if ($this->skipGlobalInstrumentations && self::isGlobalInstrumentation($instrumentation)) {
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

    private static function isGlobalInstrumentation(Instrumentation $instrumentation): bool {
        $reflection = new ReflectionParameter($instrumentation->register(...), 0);

        return $reflection->getType()?->allowsNull() ?? false;
    }
}
