<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;

interface LoaderRegistry {

    /**
     * @template T
     * @param class-string<T> $type
     * @return T
     * @throws ConfigurationException
     */
    public function load(string $type, array $config, Context $context): mixed;

    /**
     * @template T
     * @param class-string<T> $type
     * @return T|null
     * @throws ConfigurationException
     */
    public function loadNullable(string $type, ?array $config, Context $context): mixed;
}
