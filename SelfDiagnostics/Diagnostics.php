<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\SelfDiagnostics;

use Nevay\OTelSDK\Common\InstrumentationScope;

/**
 * @internal
 */
final class Diagnostics {

    private const SELF_DIAGNOSTICS_ATTRIBUTE = 'php.otel.sdk.self-diagnostics';

    public static function markAsSelfDiagnostics(iterable $attributes): iterable {
        yield from $attributes;
        yield self::SELF_DIAGNOSTICS_ATTRIBUTE => true;
    }

    public static function isSelfDiagnostics(InstrumentationScope $instrumentationScope): bool {
        return $instrumentationScope->attributes->get(self::SELF_DIAGNOSTICS_ATTRIBUTE) === true;
    }
}
