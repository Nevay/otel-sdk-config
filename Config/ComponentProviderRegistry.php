<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

/**
 * A registry of component providers.
 */
interface ComponentProviderRegistry {

    /**
     * Returns all registered providers for a specific component type.
     *
     * @param string $type the component type to load providers for
     * @return array<string, ComponentProvider> component providers indexed by their name
     */
    public function getProviders(string $type): array;
}
