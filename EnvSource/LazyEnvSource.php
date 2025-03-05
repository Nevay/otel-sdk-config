<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\EnvSource;

use Closure;
use Nevay\OTelSDK\Configuration\Environment\EnvSource;

/**
 * @internal
 */
final class LazyEnvSource implements EnvSource {

    /**
     * @param Closure(): EnvSource|EnvSource $env
     */
    public function __construct(
        private Closure|EnvSource $env,
    ) {}

    public function readRaw(string $name): mixed {
        if (!$this->env instanceof EnvSource) {
            $this->env = ($this->env)();
        }

        return $this->env->readRaw($name);
    }
}
