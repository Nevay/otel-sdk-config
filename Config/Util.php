<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config;

use InvalidArgumentException;
use function array_column;
use function array_filter;
use function count;
use function explode;
use function rawurldecode;
use function sprintf;
use function trim;

/**
 * @internal
 */
final class Util {

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
