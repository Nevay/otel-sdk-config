<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\NoopResponsePropagator;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;

/**
 * @internal
 */
final class ProxyResponsePropagator implements ResponsePropagatorInterface {

    public function __construct(
        public ResponsePropagatorInterface $propagator = new NoopResponsePropagator(),
    ) {}

    public function inject(mixed &$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void {
        $this->propagator->inject($carrier, $setter, $context);
    }
}
