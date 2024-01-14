<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Cache;

use Symfony\Component\Config\Resource\ResourceInterface;

final class ResourceCollector implements ResourceCollection {

    private array $resources = [];

    public function add(ResourceInterface $resource): void {
        $this->resources[] = $resource;
    }

    public function toArray(): array {
        return $this->resources;
    }
}
