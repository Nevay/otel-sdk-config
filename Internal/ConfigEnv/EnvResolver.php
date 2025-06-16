<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\ConfigEnv;

use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function count;
use function explode;
use function filter_var;
use function in_array;
use function rawurldecode;
use function strcasecmp;
use function trim;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

final class EnvResolver implements \OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver {

    public function __construct(
        private readonly EnvReader $envReader,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function string(string $name): ?string {
        return $this->envReader->read($name);
    }

    public function enum(string $name, array $values): ?string {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        if (in_array($value, $values, true)) {
            return $value;
        }

        $this->logger->warning('Invalid environment variable value for "{name}", expected any of {values}, got {value}', [
            'name' => $name,
            'value' => $value,
            'values' => $values,
        ]);

        return null;
    }

    public function bool(string $name): ?bool {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        if (!strcasecmp('true', $value)) {
            return true;
        }
        if (!strcasecmp('false', $value)) {
            return false;
        }

        $this->logger->warning('Invalid environment variable value for "{name}", expected boolean value, got "{value}"', [
            'name' => $name,
            'value' => $value,
        ]);

        return null;
    }

    public function int(string $name, ?int $min = 0, ?int $max = ~(-1 << 31)): int|null {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        if (($filtered = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]])) !== false) {
            return $filtered;
        }

        $this->logger->warning('Invalid environment variable value for "{name}", expected integer value in range [{min}, {max}], got "{value}"', [
            'name' => $name,
            'value' => $value,
            'min' => $min,
            'max' => $max,
        ]);

        return null;
    }

    public function numeric(string $name, int|float|null $min = 0, int|float|null $max = ~(-1 << 31)): float|int|null {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => $min, 'max_range' => $max]]) !== false) {
            return +$value;
        }

        $this->logger->warning('Invalid environment variable value for "{name}", expected numeric value in range [{min}, {max}], got "{value}"', [
            'name' => $name,
            'value' => $value,
            'min' => $min,
            'max' => $max,
        ]);

        return null;
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
                $this->logger->warning('Invalid environment variable value for "{name}", expected map value, got invalid map entry "{entry}"', [
                    'name' => $name,
                    'entry' => $entry,
                ]);

                continue;
            }

            $key = trim($member[0], " \t");
            $val = trim($member[1], " \t");

            if (isset($map[$key])) {
                $this->logger->warning('Invalid environment variable value for "{name}", expected map value, got duplicate map entry "{key}"', [
                    'name' => $name,
                    'key' => $key,
                ]);

                continue;
            }

            $map[$key] = rawurldecode($val);
        }

        return $map;
    }
}
