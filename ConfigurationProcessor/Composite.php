<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigurationProcessor;

use Nevay\OTelSDK\Configuration\ConfigurationProcessor;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;

final class Composite implements ConfigurationProcessor {

    /**
     * @param iterable<ConfigurationProcessor> $processors
     */
    public function __construct(
        private readonly iterable $processors,
    ) {}

    public function process(
        TracerProviderBuilder $tracerProviderBuilder,
        MeterProviderBuilder $meterProviderBuilder,
        LoggerProviderBuilder $loggerProviderBuilder,
    ): void {
        foreach ($this->processors as $processor) {
            $processor->process($tracerProviderBuilder, $meterProviderBuilder, $loggerProviderBuilder);
        }
    }
}
