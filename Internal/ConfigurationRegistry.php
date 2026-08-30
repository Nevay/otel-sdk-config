<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use OpenTelemetry\API\Configuration\ConfigProperties;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\InstrumentationConfiguration;

/**
 * @internal
 */
final class ConfigurationRegistry implements ConfigProperties {

    public array $configurations = [];

    public function add(InstrumentationConfiguration $configuration): self {
        $this->configurations[$configuration::class] = $configuration;

        return $this;
    }

    /**
     * @template C of InstrumentationConfiguration
     * @param class-string<C> $id
     * @return C|null
     */
    public function get(string $id): ?InstrumentationConfiguration {
        return $this->configurations[$id] ?? null;
    }
}
