<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config;

use Closure;
use Composer\InstalledVersions;
use InvalidArgumentException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Path;
use function array_column;
use function array_filter;
use function assert;
use function class_exists;
use function count;
use function explode;
use function is_string;
use function rawurldecode;
use function sprintf;
use function trim;

/**
 * @internal
 */
final class Util {

    public static function ensurePath(): Closure {
        return static function(mixed $value): ?string {
            if ($value === null) {
                return null;
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException('must be of type string');
            }

            return self::makePathAbsolute($value);
        };
    }

    public static function makePathAbsolute(?string $path): ?string {
        if ($path === null) {
            return null;
        }

        $installPath = InstalledVersions::getRootPackage()['install_path'];
        if (class_exists(Path::class)) {
            return Path::makeAbsolute($path, $installPath);
        }

        if ($path === '') {
            return $installPath;
        }

        $loader = new FileLocator($installPath);
        try {
            return $loader->locate($path);
        } catch (FileLocatorFileNotFoundException $e) {
            foreach ($e->getPaths() as $path) {
                return $path;
            }

            assert(false);
        }
    }

    /**
     * @param list<array{name: string, value: mixed}> $entries
     */
    public static function parseMapList(array $entries, ?string $listEntries): array {
        $map = array_column($entries, 'value', 'name');
        $map = array_filter($map, static fn(mixed $value) => $value !== null);
        if ($listEntries === null) {
            return $map;
        }

        foreach (explode(',', $listEntries) as $entry) {
            $member = explode('=', $entry, 2);
            if (count($member) !== 2) {
                throw new InvalidArgumentException(sprintf('Expected map value, got invalid map entry "%s"', $entry));
            }

            $key = trim($member[0], " \t");
            $val = trim($member[1], " \t");
            $map[$key] ??= rawurldecode($val);
        }

        return $map;
    }
}
