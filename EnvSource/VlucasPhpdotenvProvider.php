<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\EnvSource;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Nevay\OTelSDK\Configuration\Config\Util;
use Nevay\OTelSDK\Configuration\Environment\ArrayEnvSource;
use Nevay\OTelSDK\Configuration\Environment\EnvSource;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use function array_diff_key;

#[PackageDependency('vlucas/phpdotenv', '^4.0 || ^5.0')]
final class VlucasPhpdotenvProvider implements EnvSourceProvider {

    public function getEnvSource(): EnvSource {
        $backup = [$_SERVER, $_ENV];
        $env = [];
        try {
            Dotenv::createImmutable(Util::makePathAbsolute(''))->load();
            $env = $_SERVER;
        } catch (InvalidPathException) {
        } finally {
            [$_SERVER, $_ENV] = $backup;
        }

        return new ArrayEnvSource(array_diff_key($env, $_SERVER));
    }
}
