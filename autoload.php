<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Amp\DeferredFuture;
use Amp\Future;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Environment\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Logging\ApiLoggerHolderLogger;
use Nevay\OTelSDK\Configuration\Logging\LoggerHandler;
use Nevay\OTelSDK\Configuration\Logging\NoopHandler;
use Nevay\OTelSDK\Deferred\Deferred;
use Nevay\OTelSDK\Deferred\DeferredLoggerProvider;
use Nevay\OTelSDK\Deferred\DeferredMeterProvider;
use Nevay\OTelSDK\Deferred\DeferredTracerProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function class_exists;
use function register_shutdown_function;

(static function(): void {
    /** @var Future<ConfigurationResult|null> $config */
    $config = async(static function(): ?ConfigurationResult {
        $env = new EnvResolver(new EnvSourceReader([
            new ServerEnvSource(),
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

        $logger = new Logger('otel');
        $logger->pushHandler($handler);

        $deferredTracerProvider = null;
        $deferredMeterProvider = null;
        $deferredLoggerProvider = null;
        $context = new Context(logger: $logger);
        if ($selfDiagnostics && class_exists(Deferred::class)) {
            $deferredTracerProvider = new DeferredFuture();
            $deferredMeterProvider = new DeferredFuture();
            $deferredLoggerProvider = new DeferredFuture();
            $context = new Context(
                tracerProvider: new DeferredTracerProvider($deferredTracerProvider->getFuture()),
                meterProvider: new DeferredMeterProvider($deferredMeterProvider->getFuture()),
                logger: $logger->pushHandler(new LoggerHandler(new DeferredLoggerProvider($deferredLoggerProvider->getFuture()), $logLevel)),
            );
        }

        $config = ($configFile = $env->string('OTEL_EXPERIMENTAL_CONFIG_FILE')) !== null
            ? Config::loadFile($configFile, context: $context)
            : Env::load($context);

        $deferredTracerProvider?->complete($config->tracerProvider);
        $deferredMeterProvider?->complete($config->meterProvider);
        $deferredLoggerProvider?->complete($config->loggerProvider);

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
