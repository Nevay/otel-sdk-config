<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Configuration\Env\EnvResolver;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Environment\ServerEnvSource;
use Nevay\OTelSDK\Configuration\EnvSource\EnvSourceProvider;
use Nevay\OTelSDK\Configuration\EnvSource\LazyEnvSource;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\NoopHookManager;
use OpenTelemetry\API\Instrumentation\Configurator;
use Throwable;
use function Amp\async;
use function register_shutdown_function;

(static function(): void {
    $config = async(static function(): ?ConfigurationResult {
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

        $config = ($configFile = $env->path('OTEL_EXPERIMENTAL_CONFIG_FILE')) !== null
            ? Config::loadFile($configFile, envReader: $envReader)
            : Env::load(envReader: $envReader);

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

    $instrumentations = ServiceLoader::load(Instrumentation::class);
    if (!$instrumentations->getIterator()->valid()) {
        return;
    }
    if (!$config = $config->catch(static fn() => null)->await()) {
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
            $config->logger->warning('Error during instrumentation registration', ['exception' => $e, 'instrumentation' => $instrumentation]);
        }
    }
})();
