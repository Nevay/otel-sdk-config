<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\ConfigEnv;

use Nevay\OTelSDK\Configuration\Env\EnvReader;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class DebugEnvReader implements EnvReader {

    public function __construct(
        private readonly EnvReader $envReader,
        private readonly LoggerInterface $logger,
    ) {}

    public function read(string $name): ?string {
        $this->logger->debug('Reading env variable "{env}"', ['env' => $name]);

        return $this->envReader->read($name);
    }
}
