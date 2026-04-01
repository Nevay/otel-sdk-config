<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use Amp\Cancellation;
use Closure;
use Nevay\OTelSDK\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\LoggerInterface;

/**
 * @internal
 */
final class LoggerProvider implements LoggerProviderInterface {

    public function __construct(
        private readonly LoggerProviderInterface $loggerProvider,
    ) {}

    public function getLogger(string $name, ?string $version = null, ?string $schemaUrl = null, iterable $attributes = []): LoggerInterface {
        return $this->loggerProvider->getLogger($name, $version, $schemaUrl, Diagnostics::markAsSelfDiagnostics($attributes));
    }

    public function update(Closure $update): void {
        $this->loggerProvider->update($update);
    }

    public function shutdown(?Cancellation $cancellation = null): bool {
        return $this->loggerProvider->shutdown($cancellation);
    }

    public function forceFlush(?Cancellation $cancellation = null): bool {
        return $this->loggerProvider->forceFlush($cancellation);
    }
}

