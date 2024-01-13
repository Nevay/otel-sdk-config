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

    public function name(): string;
}
