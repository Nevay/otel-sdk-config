<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Common\Provider;
use Nevay\OTelSDK\Logs\LoggerProviderInterface;
use Nevay\OTelSDK\Metrics\MeterProviderInterface;
use Nevay\OTelSDK\Trace\TracerProviderInterface;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Psr\Log\LoggerInterface;

final class ConfigurationResult {

    /**
     * @internal
     */
    public function __construct(
        public readonly TextMapPropagatorInterface $propagator,
        public readonly TracerProviderInterface $tracerProvider,
        public readonly MeterProviderInterface $meterProvider,
        public readonly LoggerProviderInterface $loggerProvider,
        public readonly Provider $provider,
        public readonly ConfigProperties $configProperties,
        public readonly LoggerInterface $logger,
    ) {}
}
