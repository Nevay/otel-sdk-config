<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * @internal
 */
final class ConfigurationLoader {

    private array $configurations = [];
    private readonly ?ResourceCollection $resources;

    public function __construct(?ResourceCollection $resources) {
        $this->resources = $resources;
    }

    public function loadConfiguration(mixed $configuration): void {
        $this->configurations[] = $configuration;
    }

    public function addResource(ResourceInterface $resource): void {
        $this->resources?->addResource($resource);
    }

    public function getConfigurations(): array {
        return $this->configurations;
    }
}
