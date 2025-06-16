<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

interface EnvSource {

    public function readRaw(string $name): mixed;
}
