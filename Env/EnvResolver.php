<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use function count;
use function explode;
use function rawurldecode;
use function strcasecmp;
use function trim;

final class EnvResolver {

    public function __construct(
        private readonly EnvReader $envReader,
    ) {}

    public function string(string $name): ?string {
        return $this->envReader->read($name);
    }

    public function bool(string $name): ?bool {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        return !strcasecmp('true', $value);
    }

    public function numeric(string $name): float|int|null {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        return +$value;
    }

    public function list(string $name): ?array {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        $list = [];
        foreach (explode(',', $value) as $entry) {
            $list[] = rawurldecode(trim($entry, " \t"));
        }

        return $list;
    }

    public function map(string $name): ?array {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        $map = [];
        foreach (explode(',', $value) as $entry) {
            $member = explode('=', $entry, 2);
            if (count($member) !== 2) {
                continue;
            }

            $key = trim($member[0], " \t");
            $val = trim($member[1], " \t");

            $map[$key] = rawurldecode($val);
        }

        return $map;
    }
}
