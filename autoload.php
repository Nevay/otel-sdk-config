<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Nevay\OTelSDK\Configuration\Customization\Customizations;
use Nevay\OTelSDK\Configuration\Customization\RegisterAutoInstrumentations;
use Nevay\OTelSDK\Configuration\Customization\RegisterGlobals;
use Nevay\OTelSDK\Configuration\Customization\RegisterShutdownHook;
use Nevay\OTelSDK\Configuration\Customization\SelfDiagnostics;
use Nevay\OTelSDK\Configuration\Env\EnvSourceProvider;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\LazyEnvSource;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Env\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvResolver;
use Nevay\OTelSDK\Configuration\Internal\ContextKeys;
use Nevay\OTelSDK\Configuration\Internal\HookManager;
use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\Instrumentation;
use OpenTelemetry\API\Instrumentation\Configurator;
use Throwable;

(static function(): void {
    $envSources = [];
    $envSources[] = new ServerEnvSource();
    $envSources[] = new PhpIniEnvSource();
    foreach (ServiceLoader::load(EnvSourceProvider::class) as $provider) {
        $envSources[] = new LazyEnvSource($provider->getEnvSource(...));
    }

    $envReader = new EnvSourceReader($envSources);
    $env = new EnvResolver($envReader);

    $instrumentations = ServiceLoader::load(Instrumentation::class);
    $hookManager = new HookManager(defaultEnabled: true, contextKey: ContextKeys::HooksEnabled);
    $sdkHookManager = new HookManager(defaultEnabled: false, contextKey: ContextKeys::SdkHooksEnabled);

    $customization = new Customizations(
        new RegisterGlobals(),
        new RegisterAutoInstrumentations($instrumentations, $hookManager),
        new SelfDiagnostics(new RegisterAutoInstrumentations($instrumentations, $sdkHookManager)),
        new RegisterShutdownHook(),
    );

    $context = Configurator::createNoop()->storeInContext();
    $context = $hookManager->disable($context);
    $context = $sdkHookManager->enable($context);
    $scope = $context->activate();
    try {
        if (($configFile = $env->string('OTEL_CONFIG_FILE') ?? $env->string('OTEL_EXPERIMENTAL_CONFIG_FILE')) !== null) {
            Config::loadFile(Util::makePathAbsolute($configFile), envReader: $envReader, customization: $customization);
        } elseif ($env->bool('OTEL_PHP_AUTOLOAD_ENABLED')) {
            Config::loadFromEnv(envReader: $envReader, customization: $customization);
        }
    } catch (Throwable $e) {
        $logger = new Logger('otel');
        $logger->pushHandler(new ErrorLogHandler());
        $logger->error('Error during OpenTelemetry initialization: {exception}', ['exception' => $e]);
    } finally {
        $scope->detach();
    }
})();
