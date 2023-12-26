<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

use function get_cfg_var;
use function implode;
use function is_array;

final class PhpIniEnvSource implements EnvSource {

    public function raw(string $name): ?string {
        $value = get_cfg_var($name);
        if ($value === false) {
            return null;
        }
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
