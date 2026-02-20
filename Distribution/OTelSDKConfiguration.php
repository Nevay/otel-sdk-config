<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Distribution;

final class OTelSDKConfiguration implements DistributionConfiguration {

    public function __construct(
        public readonly ?float $shutdownTimeout = null,
    ) {}
}
