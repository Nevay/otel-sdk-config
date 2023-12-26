<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;

interface ConfigurationProcessor {

    public function process(
        TracerProviderBuilder $tracerProviderBuilder,
        MeterProviderBuilder $meterProviderBuilder,
        LoggerProviderBuilder $loggerProviderBuilder,
    ): void;
}
