<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;

/**
 * @internal
 */
final class MeterProvider implements MeterProviderInterface {

    public function __construct(
        private readonly MeterProviderInterface $meterProvider,
    ) {}

    public function getMeter(string $name, ?string $version = null, ?string $schemaUrl = null, iterable $attributes = []): MeterInterface {
        return $this->meterProvider->getMeter($name, $version, $schemaUrl, Diagnostics::markAsSelfDiagnostics($attributes));
    }
}
