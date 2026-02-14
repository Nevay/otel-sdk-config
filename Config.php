<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Exception;
use Nevay\OTelSDK\Configuration\Config\OpenTelemetryConfiguration;
use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Nevay\OTelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Env\ServerEnvSource;
use Nevay\OTelSDK\Configuration\Internal\Config\ConfigurationFactory;
use Nevay\OTelSDK\Configuration\Internal\ConfigEnv\EnvFactory;
use Nevay\SPI\ServiceLoader;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\Context;
use WeakMap;

final class Config {

    /**
     * Loads SDK components from environment variables.
     *
     * @return ConfigurationResult created SDK components
     * @throws Exception if the configuration is invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk-environment-variables/
     */
    public static function loadFromEnv(
        ?EnvReader $envReader = null,
        ?Customization $customization = null,
    ): ConfigurationResult {
        return self::envFactory($envReader)
            ->create(self::createContext($customization));
    }

    /**
     * Loads SDK components from a configuration file.
     *
     * Requires either `symfony/yaml` or `ext-yaml`.
     *
     * @param string|array $configFile config file(s) to load
     * @param string|null $cacheFile optional file to cache configuration to
     * @param bool $debug whether debug mode is enabled, enables checking of resources for cache
     *        freshness
     * @return ConfigurationResult created SDK components
     * @throws Exception if the configuration is invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk/
     */
    public static function loadFile(
        string|array $configFile,
        ?string $cacheFile = null,
        bool $debug = true,
        ?EnvReader $envReader = null,
        ?Customization $customization = null,
    ): ConfigurationResult {
        return self::factory($envReader)
            ->parseFile($configFile, $cacheFile, $debug)
            ->create(self::createContext($customization));
    }

    /**
     * Loads SDK components from the given array config.
     *
     * The array config structure matches the file-based structure.
     *
     * @param array $config config to load
     * @return ConfigurationResult created SDK components
     * @throws Exception if the configuration is invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk/
     */
    public static function load(
        array $config,
        ?EnvReader $envReader = null,
        ?Customization $customization = null,
    ): ConfigurationResult {
        return self::factory($envReader)
            ->process([$config])
            ->create(self::createContext($customization));
    }

    private static function createContext(?Customization $customization): Context {
        $context = new Context();
        if ($customization) {
            $context = $context->withExtension($customization, Customization::class);
        }

        return $context;
    }

    private static function factory(?EnvReader $envReader): ConfigurationFactory {
        $envReader ??= self::defaultEnvReader();

        static $factories = new WeakMap();
        return $factories[$envReader] ??= new ConfigurationFactory(
            ServiceLoader::load(ComponentProvider::class),
            new OpenTelemetryConfiguration(),
            $envReader,
        );
    }

    private static function envFactory(?EnvReader $envReader): EnvFactory {
        $envReader ??= self::defaultEnvReader();

        static $factories = new WeakMap();
        return $factories[$envReader] ??= new EnvFactory(
            ServiceLoader::load(EnvComponentLoader::class),
            $envReader,
        );
    }

    private static function defaultEnvReader(): EnvReader {
        static $defaultEnvReader = new EnvSourceReader([
            new ServerEnvSource(),
            new PhpIniEnvSource(),
        ]);

        return $defaultEnvReader;
    }
}
