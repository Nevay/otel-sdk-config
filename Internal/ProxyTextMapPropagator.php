<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\Propagation\NoopTextMapPropagator;
use OpenTelemetry\Context\Propagation\PropagationGetterInterface;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

/**
 * @internal
 */
final class ProxyTextMapPropagator implements TextMapPropagatorInterface {

    public function __construct(
        public TextMapPropagatorInterface $propagator = new NoopTextMapPropagator(),
    ) {}

    public function fields(): array {
        return $this->propagator->fields();
    }

    public function inject(mixed &$carrier, ?PropagationSetterInterface $setter = null, ?ContextInterface $context = null): void {
        $this->propagator->inject($carrier, $setter, $context);
    }

    public function extract($carrier, ?PropagationGetterInterface $getter = null, ?ContextInterface $context = null): ContextInterface {
        return $this->propagator->extract($carrier, $getter, $context);
    }
}
