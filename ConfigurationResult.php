<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Logs\LoggerProviderInterface;
use Nevay\OTelSDK\Metrics\MeterProviderInterface;
use Nevay\OTelSDK\Trace\TracerProviderInterface;
use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use Psr\Log\LoggerInterface;

final class ConfigurationResult {

    public readonly TextMapPropagatorInterface $propagator;
    public readonly TracerProviderInterface $tracerProvider;
    public readonly MeterProviderInterface $meterProvider;
    public readonly LoggerProviderInterface $loggerProvider;

    public readonly ConfigProperties $configProperties;

    /** @internal */
    public readonly LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        TextMapPropagatorInterface $propagator,
        TracerProviderInterface $tracerProvider,
        MeterProviderInterface $meterProvider,
        LoggerProviderInterface $loggerProvider,
        ConfigProperties $configProperties,
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;
        $this->configProperties = $configProperties;
        $this->loggerProvider = $loggerProvider;
        $this->meterProvider = $meterProvider;
        $this->tracerProvider = $tracerProvider;
        $this->propagator = $propagator;
    }
}
