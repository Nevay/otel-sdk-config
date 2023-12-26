<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\ConfigurationProcessor;

use Nevay\OtelSDK\Configuration\ConfigurationProcessor;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;

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
