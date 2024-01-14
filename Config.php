<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Configuration\Config\ConfigurationFactory;
use Nevay\OtelSDK\Configuration\Env\ArrayEnvSource;
use Nevay\OtelSDK\Configuration\Env\EnvSourceReader;
use Nevay\OtelSDK\Configuration\Env\EnvSource;
use Nevay\OtelSDK\Configuration\Env\PhpIniEnvSource;
use Nevay\OtelSDK\Configuration\Exception\ConfigurationException;

final class Config {

    /**
     * @throws ConfigurationException
     */
    public static function load(
        array $config,
        Context $context = new Context(),
        EnvSource ...$envSources,
    ): ConfigurationResult {
        $envReader = new EnvSourceReader($envSources ?: [
            new ArrayEnvSource($_SERVER),
            new PhpIniEnvSource(),
        ]);

        return ConfigurationFactory::create($envReader)
            ->process([$config])
            ->create($context);
    }
}
