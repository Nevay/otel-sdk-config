<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Context;
use Nevay\OTelSDK\Configuration\Exception\ConfigurationException;

interface LoaderRegistry {

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     * @throws ConfigurationException
     */
    public function load(string $type, string $name, EnvResolver $env, Context $context): mixed;

    /**
     * @template T
     * @param class-string<T> $type
     * @return T|null
     * @throws ConfigurationException
     */
    public function loadNullable(string $type, ?string $name, EnvResolver $env, Context $context): mixed;
}
