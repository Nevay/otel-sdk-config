<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

final class ArrayEnvSource implements EnvSource {

    public function __construct(
        private readonly array $env,
    ) {}

    public function raw(string $name): ?string {
        return $this->env[$name] ?? null;
    }
}
