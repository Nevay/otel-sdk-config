<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Env;

use Nevay\OtelSDK\Configuration\Context;

/**
 * @template T
 */
interface Loader {

    /**
     * @return T
     */
    public function load(EnvResolver $env, LoaderRegistry $registry, Context $context): mixed;

    /**
     * @return class-string<T>
     */
    public function type(): string;

    public function name(): string;

    /**
     * @return array<string, string>
     */
    public function dependencies(): array;
}
