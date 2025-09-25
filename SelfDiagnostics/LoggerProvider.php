<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\LoggerProviderInterface;

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
}

