<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

interface EnvSource {

    public function raw(string $name): ?string;
}
