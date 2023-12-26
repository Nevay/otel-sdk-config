<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\ConfigurationProcessor;

use Nevay\OtelSDK\Common\Resource;
use Nevay\OtelSDK\Configuration\ConfigurationProcessor;
use Nevay\OtelSDK\Logs\LoggerProviderBuilder;
use Nevay\OtelSDK\Metrics\MeterProviderBuilder;
use Nevay\OtelSDK\Trace\TracerProviderBuilder;

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
