<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

interface EnvSourceProvider {

    public function getEnvSource(): EnvSource;
}
