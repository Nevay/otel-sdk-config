<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use Nevay\OTelSDK\Configuration\Internal\Util;
use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use function array_diff_key;

#[PackageDependency('symfony/dotenv', '^5.4 || ^6.4 || ^7.0')]
final class SymfonyDotenvProvider implements EnvSourceProvider {

    public function getEnvSource(): EnvSource {
        $backup = [$_SERVER, $_ENV];
        $env = [];
        try {
            (new Dotenv())->bootEnv(Util::makePathAbsolute('.env'));
            $env = $_SERVER;
        } catch (PathException) {
        } finally {
            [$_SERVER, $_ENV] = $backup;
        }

        return new ArrayEnvSource(array_diff_key($env, $_SERVER));
    }
}
