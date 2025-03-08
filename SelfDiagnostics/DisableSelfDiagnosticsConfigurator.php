<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use Nevay\OTelSDK\Common\Configurator;
use Nevay\OTelSDK\Common\InstrumentationScope;
use Nevay\OTelSDK\Logs\LoggerConfig;
use Nevay\OTelSDK\Metrics\MeterConfig;
use Nevay\OTelSDK\Trace\TracerConfig;

/**
 * @implements Configurator<TracerConfig|MeterConfig|LoggerConfig>
 *
 * @internal
 */
final class DisableSelfDiagnosticsConfigurator implements Configurator {

    private const SELF_DIAGNOSTICS_ATTRIBUTE = 'php.otel.sdk.self-diagnostics';

    public static function markAsSelfDiagnostics(iterable $attributes): iterable {
        yield from $attributes;
        yield self::SELF_DIAGNOSTICS_ATTRIBUTE => true;
    }

    public function update(mixed $config, InstrumentationScope $instrumentationScope): bool {
        if (!$this->appliesTo($instrumentationScope)) {
            return false;
        }

        $config->disabled = true;

        return true;
    }

    public function appliesTo(InstrumentationScope $instrumentationScope): bool {
        return $instrumentationScope->attributes->get(self::SELF_DIAGNOSTICS_ATTRIBUTE) === true;
    }
}
