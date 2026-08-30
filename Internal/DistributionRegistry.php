<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\Distribution\DistributionConfiguration;
use Nevay\OTelSDK\Configuration\Distribution\DistributionProperties;

/**
 * @internal
 */
final class DistributionRegistry implements DistributionProperties {

    public array $distributionConfigurations = [];

    public function add(DistributionConfiguration $distributionConfiguration): self {
        $this->distributionConfigurations[$distributionConfiguration::class] = $distributionConfiguration;

        return $this;
    }

    /**
     * @template C of DistributionConfiguration
     * @param class-string<C> $distribution
     * @return C|null
     */
    public function getDistributionConfiguration(string $distribution): ?DistributionConfiguration {
        return $this->distributionConfigurations[$distribution] ?? null;
    }
}
