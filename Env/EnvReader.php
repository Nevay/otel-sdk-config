<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

interface EnvReader {

    public function read(string $name): ?string;
}
