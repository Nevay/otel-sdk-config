<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Common\Provider\MultiProvider;
use Nevay\OTelSDK\Configuration\Env\EnvSourceProvider;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\LazyEnvSource;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Env\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvResolver;
use Nevay\OTelSDK\Configuration\Internal\Util;
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

        if (($configFile = $env->string('OTEL_EXPERIMENTAL_CONFIG_FILE')) !== null) {
            $config = Config::loadFile(Util::makePathAbsolute($configFile), envReader: $envReader);
        } elseif ($env->bool('OTEL_PHP_AUTOLOAD_ENABLED')) {
            $config = Env::load(envReader: $envReader);
        } else {
            return null;
        }

        // Re-register to trigger after normal shutdown functions
        register_shutdown_function(
            register_shutdown_function(...),
            (new MultiProvider([
                $config->tracerProvider,
                $config->meterProvider,
                $config->loggerProvider,
            ]))->shutdown(...),
        );

        return $config;
    });
    $config = $config->catch(static function(Throwable $e): ?ConfigurationResult {
        $logger = new Logger('otel');
        $logger->pushHandler(new ErrorLogHandler());
        $logger->error('Error during OpenTelemetry initialization: {exception}', ['exception' => $e]);

        return null;
    });

    Globals::registerInitializer(static function(Configurator $configurator) use ($config): Configurator {
        if ($config = $config->await()) {
            $configurator = $configurator
                ->withPropagator($config->propagator)
                ->withResponsePropagator($config->responsePropagator)
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
    if (!$config = $config->await()) {
        return;
    }

    $hookManager = ServiceLoader::load(HookManagerInterface::class)->getIterator()->current() ?? new NoopHookManager();
    $context = new AutoInstrumentation\Context(
        tracerProvider: $config->tracerProvider,
        meterProvider: $config->meterProvider,
        loggerProvider: $config->loggerProvider,
        propagator: $config->propagator,
        responsePropagator: $config->responsePropagator,
    );
    foreach ($instrumentations as $instrumentation) {
        try {
            $instrumentation->register($hookManager, $config->configProperties, $context);
        } catch (Throwable $e) {
            $config->logger->warning('Error during instrumentation registration', ['exception' => $e, 'instrumentation' => $instrumentation]);
        }
    }
})();
