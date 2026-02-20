<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Distribution;

interface DistributionProperties {

    /**
     * @template T of DistributionConfiguration
     * @param class-string<T> $distribution
     * @return DistributionConfiguration|null
     */
    public function getDistributionConfiguration(string $distribution): ?DistributionConfiguration;
}
