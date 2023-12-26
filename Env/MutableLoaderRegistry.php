<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Exception;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;
use Nevay\OtelSDK\Configuration\Exception\LoaderNotFoundException;
use Nevay\OtelSDK\Configuration\Exception\UnhandledLoaderException;
use Nevay\OtelSDK\Configuration\Exception\UnmetPackageDependencyException;
use function sprintf;

/**
 * @internal
 */
final class MutableLoaderRegistry implements LoaderRegistry {

    /**
     * @var array<class-string, array<string, Loader>>
     */
    private array $loaders = [];

    public function register(Loader $loader): LoaderRegistry {
        $this->loaders[$loader->type()][$loader->name()] ??= $loader;

        return $this;
    }

    public function load(string $type, string $name, EnvResolver $env, Context $context): mixed {
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
            return $loader->load($env, $this, $context);
        } catch (ConfigurationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new UnhandledLoaderException($e->getMessage(), previous: $e);
        }
    }

    public function loadNullable(string $type, ?string $name, EnvResolver $env, Context $context): mixed {
        return $name !== null
            ? $this->load($type, $name, $env, $context)
            : null;
    }
}
