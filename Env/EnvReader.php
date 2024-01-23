<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

interface EnvReader {

    public function read(string $name): ?string;
}
