<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Closure;
use Composer\InstalledVersions;
use InvalidArgumentException;
use OpenTelemetry\API\Logs\Severity;
use Psr\Log\LogLevel;
use ReflectionEnumBackedCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Path;
use function array_column;
use function array_filter;
use function array_map;
use function assert;
use function class_exists;
use function count;
use function explode;
use function is_string;
use function rawurldecode;
use function sprintf;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * @internal
 */
final class Util {

    public static function severityValues(): array {
        return array_map(strtolower(...), array_column(Severity::cases(), 'name'));
    }

    public static function severityByName(string $name): Severity {
        $severity = (new ReflectionEnumBackedCase(Severity::class, strtoupper($name)))->getValue();
        assert($severity instanceof Severity);

        return $severity;
    }

    /**
     * @see Severity::fromPsr3()
     */
    public static function severityToLogLevel(Severity $severity): string {
        return match ($severity) {
            Severity::TRACE,
            Severity::TRACE2,
            Severity::TRACE3,
            Severity::TRACE4,
            Severity::DEBUG,
            Severity::DEBUG2,
            Severity::DEBUG3,
            Severity::DEBUG4 => LogLevel::DEBUG,
            Severity::INFO => LogLevel::INFO,
            Severity::INFO2,
            Severity::INFO3,
            Severity::INFO4 => LogLevel::NOTICE,
            Severity::WARN,
            Severity::WARN2,
            Severity::WARN3,
            Severity::WARN4 => LogLevel::WARNING,
            Severity::ERROR => LogLevel::ERROR,
            Severity::ERROR2 => LogLevel::CRITICAL,
            Severity::ERROR3,
            Severity::ERROR4 => LogLevel::ALERT,
            Severity::FATAL,
            Severity::FATAL2,
            Severity::FATAL3,
            Severity::FATAL4 => LogLevel::EMERGENCY,
        };
    }

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

    public static function ensureString(): Closure {
        return static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException('must be of type string');
            }

            return $value;
        };
    }
}
