<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Context;

interface LoaderRegistry {

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     */
    public function load(string $type, string $name, EnvResolver $env, Context $context): mixed;

    /**
     * @template T
     * @param class-string<T> $type
     * @return T|null
     */
    public function loadNullable(string $type, ?string $name, EnvResolver $env, Context $context): mixed;

    /**
     * @template T
     * @param class-string<T> $type
     * @return iterable<T>
     */
    public function loadAll(string $type, EnvResolver $env, Context $context): iterable;
}
