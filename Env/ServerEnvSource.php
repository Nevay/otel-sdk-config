<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

final class ServerEnvSource implements EnvSource {

    public function readRaw(string $name): mixed {
        return $_SERVER[$name] ?? null;
    }
}
