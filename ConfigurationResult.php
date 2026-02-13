<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Logs\LoggerProviderInterface;
use Nevay\OTelSDK\Metrics\MeterProviderInterface;
use Nevay\OTelSDK\Trace\TracerProviderInterface;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\Context\Propagation\ResponsePropagatorInterface;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;

final class ConfigurationResult {

    public readonly TextMapPropagatorInterface $propagator;
    public readonly ResponsePropagatorInterface $responsePropagator;
    public readonly TracerProviderInterface $tracerProvider;
    public readonly MeterProviderInterface $meterProvider;
    public readonly LoggerProviderInterface $loggerProvider;

    public readonly ConfigProperties $configProperties;

    /**
     * @internal
     */
    public function __construct(
        TextMapPropagatorInterface $propagator,
        ResponsePropagatorInterface $responsePropagator,
        TracerProviderInterface $tracerProvider,
        MeterProviderInterface $meterProvider,
        LoggerProviderInterface $loggerProvider,
        ConfigProperties $configProperties,
    ) {
        $this->propagator = $propagator;
        $this->responsePropagator = $responsePropagator;
        $this->loggerProvider = $loggerProvider;
        $this->meterProvider = $meterProvider;
        $this->tracerProvider = $tracerProvider;
        $this->configProperties = $configProperties;
    }
}
