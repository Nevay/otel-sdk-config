<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;

interface ConfigurationProcessor {

    public function process(
        TracerProviderBuilder $tracerProviderBuilder,
        MeterProviderBuilder $meterProviderBuilder,
        LoggerProviderBuilder $loggerProviderBuilder,
    ): void;
}
