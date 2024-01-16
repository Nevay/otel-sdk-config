<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Amp\Future;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\PsrHandler;
use Nevay\OtelSDK\Configuration\ConfigurationProcessor\DetectResource;
use Nevay\OtelSDK\Configuration\Env\ArrayEnvSource;
use Nevay\OtelSDK\Configuration\Env\EnvResolver;
use Nevay\OtelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OtelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OtelSDK\Configuration\Logging\ApiLoggerHolderLogger;
use Nevay\OtelSDK\Configuration\Logging\NoopHandler;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function register_shutdown_function;

(static function(): void {
    /** @var Future<ConfigurationResult|null> $config */
    $config = async(static function(): ?ConfigurationResult {
        $env = new EnvResolver(new EnvSourceReader([
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]));
        if (!$env->bool('OTEL_PHP_AUTOLOAD_ENABLED')) {
            return null;
        }

        $logLevel = $env->string('OTEL_LOG_LEVEL') ?? 'info';
        $logDestination = $env->string('OTEL_PHP_LOG_DESTINATION') ?? 'stderr';
        $selfDiagnostics = $env->bool('OTEL_PHP_INTERNAL_METRICS_ENABLED') ?? false;

        $handler = match ($logDestination) {
            'none' => new NoopHandler($logLevel),
            'stderr' => (new StreamHandler(getStderr(), $logLevel))->setFormatter(new ConsoleFormatter()),
            'stdout' => (new StreamHandler(getStdout(), $logLevel))->setFormatter(new ConsoleFormatter()),
            'psr3' => new PsrHandler(new ApiLoggerHolderLogger(), $logLevel),
            'error_log' => new ErrorLogHandler(level: $logLevel),
        };

        $contextBuilder = ContextBuilder::create()
            ->pushLogHandler($handler)
            ->pushConfigurationProcessor(new DetectResource());
        $context = $selfDiagnostics
            ? $contextBuilder->createSelfDiagnosticsContext(logLevel: $logLevel)
            : $contextBuilder->createContext();

        if (($detectors = $env->list('OTEL_PHP_DETECTORS')) !== null && $detectors !== ['all']) {
            $context->logger->notice('Not supported environment variable OTEL_PHP_DETECTORS, using all available detectors', [
                'requested_detectors' => $detectors,
            ]);
        }

        $config = Env::load($context);
        $contextBuilder->resolve($context, $config);

        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            $config->provider->shutdown(...),
        );

        return $config;
    })->ignore();

    Globals::registerInitializer(static function(Configurator $configurator) use ($config): Configurator {
        if ($config = $config->await()) {
            $configurator = $configurator
                ->withPropagator($config->propagator)
                ->withTracerProvider($config->tracerProvider)
                ->withMeterProvider($config->meterProvider)
                ->withLoggerProvider($config->loggerProvider)
            ;
        }

        return $configurator;
    });
})();
