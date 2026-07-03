<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Closure;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;

/**
 * @internal
 */
final class HookCountingManager implements HookManagerInterface {

    public int $hooks = 0;

    public function hook(?string $class, string $function, ?Closure $preHook = null, ?Closure $postHook = null): void {
        $this->hooks++;
    }
}
