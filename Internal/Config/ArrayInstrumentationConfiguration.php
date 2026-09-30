<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use OpenTelemetry\API\Instrumentation\AutoInstrumentation\GeneralInstrumentationConfiguration;

/**
 * @internal
 */
final class ArrayInstrumentationConfiguration implements GeneralInstrumentationConfiguration {

    public function __construct(
        public readonly array $config,
        public readonly string $name,
    ) {}
}
