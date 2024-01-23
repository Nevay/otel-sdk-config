<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Context;

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
