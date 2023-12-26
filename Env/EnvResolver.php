<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

use function count;
use function explode;
use function rawurldecode;
use function strcasecmp;
use function trim;

final class EnvResolver {

    /**
     * @param iterable<EnvSource> $envs
     */
    public function __construct(
        private readonly iterable $envs,
    ) {}

    public function string(string $name): ?string {
        foreach ($this->envs as $env) {
            if (($value = $env->raw($name)) !== null && ($value = trim($value, " \t")) !== '') {
                return $value;
            }
        }

        return null;
    }

    public function bool(string $name): ?bool {
        if (($value = $this->string($name)) === null) {
            return null;
        }

        return !strcasecmp('true', $value);
    }

    public function numeric(string $name): float|int|null {
        if (($value = $this->string($name)) === null) {
            return null;
        }

        return +$value;
    }

    public function list(string $name): ?array {
        if (($value = $this->string($name)) === null) {
            return null;
        }

        $list = [];
        foreach (explode(',', $value) as $entry) {
            $list[] = rawurldecode(trim($entry, " \t"));
        }

        return $list;
    }

    public function map(string $name): ?array {
        if (($value = $this->string($name)) === null) {
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
