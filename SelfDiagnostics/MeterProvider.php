<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use Amp\Cancellation;
use Closure;
use Nevay\OTelSDK\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Metrics\MeterInterface;

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

    public function update(Closure $update): void {
        $this->meterProvider->update($update);
    }

    public function shutdown(?Cancellation $cancellation = null): bool {
        return $this->meterProvider->shutdown($cancellation);
    }

    public function forceFlush(?Cancellation $cancellation = null): bool {
        return $this->meterProvider->forceFlush($cancellation);
    }
}
