<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\EnvSource;

use Nevay\OTelSDK\Configuration\Environment\EnvSource;

interface EnvSourceProvider {

    public function getEnvSource(): EnvSource;
}
