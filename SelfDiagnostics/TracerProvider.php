<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;

/**
 * @internal
 */
final class TracerProvider implements TracerProviderInterface {

    public function __construct(
        private readonly TracerProviderInterface $tracerProvider,
    ) {}

    public function getTracer(string $name, ?string $version = null, ?string $schemaUrl = null, iterable $attributes = []): TracerInterface {
        return $this->tracerProvider->getTracer($name, $version, $schemaUrl,
            DisableSelfDiagnosticsConfigurator::markAsSelfDiagnostics($attributes));
    }
}
