<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use InvalidArgumentException;
use Nevay\OtelSDK\Configuration\Cache\EnvResourceChecker;
use Nevay\OtelSDK\Configuration\Cache\ResourceCollection;
use Nevay\OtelSDK\Configuration\Cache\ResourceCollector;
use Nevay\OtelSDK\Configuration\Config\Configuration;
use Nevay\OtelSDK\Configuration\Config\ConfigurationFactory;
use Nevay\OtelSDK\Configuration\Config\Internal\YamlExtensionFileLoader;
use Nevay\OtelSDK\Configuration\Config\Internal\YamlSymfonyFileLoader;
use Nevay\OtelSDK\Configuration\Env\ArrayEnvSource;
use Nevay\OtelSDK\Configuration\Env\EnvReader;
use Nevay\OtelSDK\Configuration\Env\EnvSource;
use Nevay\OtelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OtelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\ComposerResource;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\VarExporter\VarExporter;
use function class_exists;
use function serialize;
use function sprintf;
use function var_export;

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
     * @param EnvSource ...$envSource env sources to use, defaults to env variables and php ini
     * @return ConfigurationResult created SDK components
     * @throws ConfigurationException if the configuration is invalid
     */
    public static function loadFile(
        string|array $configFile,
        ?string $cacheFile = null,
        bool $debug = true,
        Context $context = new Context(),
        EnvSource ...$envSource,
    ): ConfigurationResult {
        $envReader = new EnvSourceReader($envSource ?: [
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]);

        $configuration = $cacheFile !== null
            ? self::loadConfigurationCached($configFile, $cacheFile, $debug, $envReader)
            : self::loadConfiguration($configFile, $envReader);

        return $configuration->create($context);
    }

    private static function loadConfigurationCached(string|array $configFile, string $cacheFile, bool $debug, EnvReader $envReader): Configuration {
        $cache = new ResourceCheckerConfigCache($cacheFile, !$debug ? [] : [
            new SelfCheckingResourceChecker(),
            new EnvResourceChecker($envReader),
        ]);
        if (!$cache->isFresh()) {
            $resources = new ResourceCollector();
            $resources->add(new ComposerResource());
            $configuration = self::loadConfiguration($configFile, $envReader, $resources);
            $content = class_exists(VarExporter::class)
                ? sprintf('<?php return %s;', VarExporter::export($configuration))
                : sprintf('<?php return unserialize(%s);', var_export(serialize($configuration), true));
            $cache->write($content, $resources->toArray());
        }

        $configuration = @include $cache->getPath();
        if (!$configuration instanceof Configuration) {
            throw new InvalidArgumentException(sprintf('Failed to load configuration from cache %s', $cache->getPath()));
        }

        return $configuration;
    }

    private static function loadConfiguration(string|array $configFile, EnvReader $envReader, ?ResourceCollection $resources = null): Configuration {
        $locator = new FileLocator();
        $loader = new DelegatingLoader(new LoaderResolver([
            new YamlSymfonyFileLoader($resources, $locator),
            new YamlExtensionFileLoader($resources, $locator),
        ]));

        $configs = [];
        foreach ((array) $configFile as $file) {
            $configs[] = $loader->load($file);
        }

        return ConfigurationFactory::create($envReader)->process($configs, $resources);
    }

    /**
     * @throws ConfigurationException
     */
    public static function load(
        array $config,
        Context $context = new Context(),
        EnvSource ...$envSources,
    ): ConfigurationResult {
        $envReader = new EnvSourceReader($envSources ?: [
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]);

        return ConfigurationFactory::create($envReader)
            ->process([$config])
            ->create($context);
    }
}
