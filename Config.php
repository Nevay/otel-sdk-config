<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Exception;
use Nevay\OTelSDK\Configuration\Config\OpenTelemetryConfiguration;
use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\OTelSDK\Configuration\Environment\ServerEnvSource;
use Nevay\SPI\ServiceLoader;
use WeakMap;

final class Config {

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
     */
    public static function loadFile(
        string|array $configFile,
        ?string $cacheFile = null,
        bool $debug = true,
        ?EnvReader $envReader = null,
    ): ConfigurationResult {
        return self::factory($envReader)
            ->parseFile($configFile, $cacheFile, $debug)
            ->create(new Context());
    }

    public static function load(
        array $config,
        ?EnvReader $envReader = null,
    ): ConfigurationResult {
        return self::factory($envReader)
            ->process([$config])
            ->create(new Context());
    }

    private static function factory(?EnvReader $envReader): ConfigurationFactory {
        static $defaultEnvReader;
        $envReader ??= $defaultEnvReader ??= new EnvSourceReader([
            new ServerEnvSource(),
            new PhpIniEnvSource(),
        ]);

        static $factories = new WeakMap();
        return $factories[$envReader] ??= new ConfigurationFactory(
            ServiceLoader::load(ComponentProvider::class),
            new OpenTelemetryConfiguration(),
            $envReader,
        );
    }
}
