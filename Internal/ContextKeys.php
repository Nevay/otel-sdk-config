<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use OpenTelemetry\Context\ContextKeyInterface;

/**
 * @internal
 */
enum ContextKeys implements ContextKeyInterface {

    case HooksEnabled;
    case SdkHooksEnabled;
}
