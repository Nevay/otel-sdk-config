<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Amp\DeferredFuture;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Environment\ServerEnvSource;
use Nevay\OTelSDK\Configuration\EnvSource\EnvSourceProvider;
use Nevay\OTelSDK\Configuration\EnvSource\LazyEnvSource;
use Nevay\OTelSDK\Configuration\Logging\ApiLoggerHolderLogger;
use Nevay\OTelSDK\Configuration\Logging\LoggerHandler;
use Nevay\OTelSDK\Configuration\Logging\NoopHandler;
use Nevay\OTelSDK\Deferred\Deferred;
use Nevay\OTelSDK\Deferred\DeferredLoggerProvider;
use Nevay\OTelSDK\Deferred\DeferredMeterProvider;
use Nevay\OTelSDK\Deferred\DeferredTracerProvider;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\NoopHookManager;
use OpenTelemetry\API\Instrumentation\Configurator;
use Throwable;
use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function class_exists;
use function register_shutdown_function;

(static function(): void {
    $config = async(static function(): ?array {
        $envSources = [];
        $envSources[] = new ServerEnvSource();
        $envSources[] = new PhpIniEnvSource();
        foreach (ServiceLoader::load(EnvSourceProvider::class) as $provider) {
            $envSources[] = new LazyEnvSource($provider->getEnvSource(...));
        }

        $envReader = new EnvSourceReader($envSources);
        $env = new EnvResolver($envReader);
        if (!$env->bool('OTEL_PHP_AUTOLOAD_ENABLED')) {
            return null;
        }

        $logLevel = $env->string('OTEL_LOG_LEVEL') ?? 'info';
        $logDestination = $env->string('OTEL_PHP_LOG_DESTINATION') ?? 'stderr';

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
        if (class_exists(Deferred::class) && $env->bool('OTEL_PHP_INTERNAL_METRICS_ENABLED')) {
            $deferredTracerProvider = new DeferredFuture();
            $deferredMeterProvider = new DeferredFuture();
            $deferredLoggerProvider = new DeferredFuture();
            $context = new Context(
                tracerProvider: new DeferredTracerProvider($deferredTracerProvider->getFuture()),
                meterProvider: new DeferredMeterProvider($deferredMeterProvider->getFuture()),
                loggerProvider: new DeferredLoggerProvider($deferredLoggerProvider->getFuture()),
                logger: $logger->pushHandler(new LoggerHandler(new DeferredLoggerProvider($deferredLoggerProvider->getFuture()), $logLevel)),
            );
        }

        try {
            $config = ($configFile = $env->path('OTEL_EXPERIMENTAL_CONFIG_FILE')) !== null
                ? Config::loadFile($configFile, context: $context, envReader: $envReader)
                : Env::load($context, envReader: $envReader);
        } catch (Throwable $e) {
            $logger->warning('Error during SDK initialization', ['exception' => $e]);

            return null;
        }

        $deferredTracerProvider?->complete($config->tracerProvider);
        $deferredMeterProvider?->complete($config->meterProvider);
        $deferredLoggerProvider?->complete($config->loggerProvider);

        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            $config->provider->shutdown(...),
        );

        return [$config, $logger];
    })->ignore();

    Globals::registerInitializer(static function(Configurator $configurator) use ($config): Configurator {
        if ([$config] = $config->await()) {
            $configurator = $configurator
                ->withPropagator($config->propagator)
                ->withTracerProvider($config->tracerProvider)
                ->withMeterProvider($config->meterProvider)
                ->withLoggerProvider($config->loggerProvider)
            ;
        }

        return $configurator;
    });

    $instrumentations = ServiceLoader::load(Instrumentation::class);
    if (!$instrumentations->getIterator()->valid()) {
        return;
    }
    if (![$config, $logger] = $config->catch(static fn() => null)->await()) {
        return;
    }

    $hookManager = ServiceLoader::load(HookManagerInterface::class)->getIterator()->current() ?? new NoopHookManager();
    $context = new AutoInstrumentation\Context(
        tracerProvider: $config->tracerProvider,
        meterProvider: $config->meterProvider,
        loggerProvider: $config->loggerProvider,
        propagator: $config->propagator,
    );
    foreach ($instrumentations as $instrumentation) {
        try {
            $instrumentation->register($hookManager, $config->configProperties, $context);
        } catch (Throwable $e) {
            $logger->warning('Error during instrumentation registration', ['exception' => $e, 'instrumentation' => $instrumentation]);
        }
    }
})();
