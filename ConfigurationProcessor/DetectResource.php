<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\ConfigurationProcessor;

use Nevay\OTelSDK\Common\Resource;
use Nevay\OTelSDK\Configuration\ConfigurationProcessor;
use Nevay\OTelSDK\Logs\LoggerProviderBuilder;
use Nevay\OTelSDK\Metrics\MeterProviderBuilder;
use Nevay\OTelSDK\Trace\TracerProviderBuilder;

final class DetectResource implements ConfigurationProcessor {

    public function process(
        TracerProviderBuilder $tracerProviderBuilder,
        MeterProviderBuilder $meterProviderBuilder,
        LoggerProviderBuilder $loggerProviderBuilder,
    ): void {
        $resource = Resource::detect();
        $tracerProviderBuilder->addResource($resource);
        $meterProviderBuilder->addResource($resource);
        $loggerProviderBuilder->addResource($resource);
    }
}
