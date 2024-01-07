<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Configuration\Config\ConfigurationFactory;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;

final class Config {

    /**
     * @throws ConfigurationException
     */
    public static function load(
        array $config,
        Context $context = new Context(),
    ): ConfigurationResult {
        return ConfigurationFactory::create()->load($context, $config);
    }
}
