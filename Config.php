<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Exception;
use Nevay\OTelSDK\Configuration\Config\Internal\ConfigurationResultOpentelemetryConfiguration;
use Nevay\OTelSDK\Configuration\Config\OpenTelemetryConfiguration;
use Nevay\OTelSDK\Configuration\ConfigurationProcessor\DetectResource;
use Nevay\OTelSDK\Configuration\Environment\ArrayEnvSource;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use Nevay\SPI\ServiceLoader;

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
     * @param Context $context context used for creation
     * @return ConfigurationResult created SDK components
     * @throws Exception if the configuration is invalid
     */
    public static function loadFile(
        string|array $configFile,
        ?string $cacheFile = null,
        bool $debug = true,
        Context $context = new Context(),
    ): ConfigurationResult {
        return self::factory()
            ->parseFile($configFile, $cacheFile, $debug)
            ->create($context);
    }

    public static function load(
        array $config,
        Context $context = new Context(),
    ): ConfigurationResult {
        return self::factory()
            ->process($config)
            ->create($context);
    }

    private static function factory(): ConfigurationFactory {
        static $factory;
        return $factory ??= new ConfigurationFactory(
            ServiceLoader::load(ComponentProvider::class),
            new OpenTelemetryConfiguration(new DetectResource()),
            new EnvSourceReader([
                new ArrayEnvSource($_SERVER),
                new PhpIniEnvSource(),
            ]),
        );
    }
}
