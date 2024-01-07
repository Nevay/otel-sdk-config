<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Attribute;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

/**
 * Specifies composer dependencies required by a {@link ComponentProvider}.
 */
#[Attribute(flags: Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ComponentProviderDependency {

    public function __construct(
        public readonly string $package,
        public readonly string $version,
    ) {}

    public function isSatisfied(): bool {
        return InstalledVersions::isInstalled($this->package)
            && InstalledVersions::satisfies(new VersionParser(), $this->package, $this->version);
    }
}
