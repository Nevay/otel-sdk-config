<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Distribution;

final class DistributionRegistry implements DistributionProperties {

    private array $distributionConfigurations = [];

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
