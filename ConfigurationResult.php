<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Common\Provider;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

final class ConfigurationResult {

    public function __construct(
        public readonly TextMapPropagatorInterface $propagator,
        public readonly TracerProviderInterface $tracerProvider,
        public readonly MeterProviderInterface $meterProvider,
        public readonly LoggerProviderInterface $loggerProvider,
        public readonly Provider $provider,
    ) {}
}
