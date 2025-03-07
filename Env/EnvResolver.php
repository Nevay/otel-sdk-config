<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Config\Util;
use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function count;
use function explode;
use function filter_var;
use function rawurldecode;
use function strcasecmp;
use function trim;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

final class EnvResolver {

    public function __construct(
        private readonly EnvReader $envReader,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Resolves a string-valued environment variable.
     *
     * @param string $name environment variable name
     * @return string|null value of the environment variable, or null if not set or invalid
     */
    public function string(string $name): ?string {
        return $this->envReader->read($name);
    }

    /**
     * Resolves a path-valued environment variable.
     *
     * @param string $name environment variable name
     * @return string|null value of the environment variable, or null if not set or invalid
     */
    public function path(string $name): ?string {
        if (($value = $this->envReader->read($name)) === null) {
            return null;
        }

        return Util::makePathAbsolute($value);
    }

    /**
     * Resolves a boolean-valued environment variable.
     *
     * Allowed values:
     * - case-insensitive "true"
     * - case-insensitive "false"
     *
     * @param string $name environment variable name
     * @return bool|null value of the environment variable, or null if not set or invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk-environment-variables/#boolean-value
     */
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

    /**
     * Resolves an integer-valued environment variable.
     *
     * @param string $name environment variable name
     * @param int|null $min lower limit (inclusive), defaults to 0
     * @param int|null $max upper limit (inclusive), defaults to 2^31-1
     * @return int|null value of the environment variable, or null if not set or invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk-environment-variables/#numeric-value
     */
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

    /**
     * Resolves a numeric-valued environment variable.
     *
     * @param string $name environment variable name
     * @param int|float|null $min lower limit (inclusive), defaults to 0
     * @param int|float|null $max upper limit (inclusive), defaults to 2^31-1
     * @return int|float|null value of the environment variable, or null if not set or invalid
     *
     * @see https://opentelemetry.io/docs/specs/otel/configuration/sdk-environment-variables/#numeric-value
     */
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

    /**
     * Resolves a list-valued environment variable.
     *
     * @param string $name environment variable name
     * @return list<string>|null value of the environment variable, or null if not set or invalid
     */
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

    /**
     * Resolves a map-valued environment variable.
     *
     * @param string $name environment variable name
     * @return array<string, string>|null value of the environment variable, or null if not set or invalid
     */
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
