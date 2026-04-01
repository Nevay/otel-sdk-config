<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use Amp\Cancellation;
use Closure;
use Nevay\OTelSDK\Trace\TracerProviderInterface;
use OpenTelemetry\API\Trace\TracerInterface;

/**
 * @internal
 */
final class TracerProvider implements TracerProviderInterface {

    public function __construct(
        private readonly TracerProviderInterface $tracerProvider,
    ) {}

    public function getTracer(string $name, ?string $version = null, ?string $schemaUrl = null, iterable $attributes = []): TracerInterface {
        return $this->tracerProvider->getTracer($name, $version, $schemaUrl, Diagnostics::markAsSelfDiagnostics($attributes));
    }

    public function update(Closure $update): void {
        $this->tracerProvider->update($update);
    }

    public function shutdown(?Cancellation $cancellation = null): bool {
        return $this->tracerProvider->shutdown($cancellation);
    }

    public function forceFlush(?Cancellation $cancellation = null): bool {
        return $this->tracerProvider->forceFlush($cancellation);
    }
}
