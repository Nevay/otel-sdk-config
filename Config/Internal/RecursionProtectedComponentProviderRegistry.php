<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config\Internal;

use Nevay\OTelSDK\Configuration\Config\ComponentProviderRegistry;

/**
 * @internal
 */
final class RecursionProtectedComponentProviderRegistry implements ComponentProviderRegistry {

    public function __construct(
        private readonly ComponentProviderRegistry $registry,
        private readonly string $type,
        private readonly string $name,
    ) {}

    public function getProviders(string $type): array {
        $providers = $this->registry->getProviders($type);
        if ($type === $this->type) {
            unset($providers[$this->name]);
        }

        return $providers;
    }
}
