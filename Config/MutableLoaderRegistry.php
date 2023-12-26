<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Exception;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;
use Nevay\OtelSDK\Configuration\Exception\InvalidConfigurationException;
use Nevay\OtelSDK\Configuration\Exception\LoaderNotFoundException;
use Nevay\OtelSDK\Configuration\Exception\UnhandledLoaderException;
use Nevay\OtelSDK\Configuration\Exception\UnmetPackageDependencyException;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class MutableLoaderRegistry implements LoaderRegistry {

    /**
     * @var array<class-string, array<string, Loader>>
     */
    private array $loaders = [];

    /**
     * @template T
     * @param Loader<T> $loader
     */
    public function register(Loader $loader): LoaderRegistry {
        $this->loaders[$loader->type()][$loader->name()] ??= $loader;

        return $this;
    }

    public function load(string $type, array $config, Context $context): mixed {
        if (count($config) !== 1) {
            throw new InvalidConfigurationException(sprintf('Cannot resolve name for type %s, config does not contain exactly one entry (%s)',
                $type, implode(',', array_map(json_encode(...), array_map(strval(...), array_keys($config)))) ?: '<empty>'));
        }

        $name = (string) array_key_first($config);
        if (!$loader = $this->loaders[$type][$name] ?? null) {
            throw new LoaderNotFoundException(sprintf('Loader for %s %s not found', $type, $name));
        }
        foreach ($loader->dependencies() as $package => $constraint) {
            if (!InstalledVersions::isInstalled($package) || !InstalledVersions::satisfies(new VersionParser(), $package, $constraint)) {
                throw new UnmetPackageDependencyException(sprintf('Loader for %s %s has unmet dependency requirement %s %s',
                    $type, $name, $package, $constraint));
            }
        }

        try {
            return $loader->load($config[$name] ?? [], $this, $context);
        } catch (ConfigurationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new UnhandledLoaderException($e->getMessage(), previous: $e);
        }
    }

    public function loadNullable(string $type, ?array $config, Context $context): mixed {
        if ($config === null || $config === []) {
            return null;
        }

        return $this->load($type, $config, $context);
    }
}
