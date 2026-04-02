<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Closure;
use OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;
use ReflectionFunction;
use function function_exists;

/**
 * @internal
 */
final class HookManager implements HookManagerInterface {

    public function __construct(
        private readonly bool $defaultEnabled,
        private readonly ContextKeyInterface $contextKey,
    ) {}

    public function hook(?string $class, string $function, ?Closure $preHook = null, ?Closure $postHook = null): void {
        if (!function_exists('OpenTelemetry\Instrumentation\hook')) {
            return;
        }

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        \OpenTelemetry\Instrumentation\hook($class, $function, $this->bindHookScope($preHook, 1), $this->bindHookScope($postHook, 2));
    }

    public function enable(?ContextInterface $context): ContextInterface {
        $context ??= Context::getCurrent();
        return $context->with($this->contextKey, true);
    }

    public function disable(?ContextInterface $context): ContextInterface {
        $context ??= Context::getCurrent();
        return $context->with($this->contextKey, false);
    }

    private function bindHookScope(?Closure $closure, int $return): ?Closure {
        if (!$closure) {
            return null;
        }

        $reflection = new ReflectionFunction($closure);
        $contextKey = $this->contextKey;
        $default = $this->defaultEnabled;

        if (!$reflection->getReturnType() || (string) $reflection->getReturnType() === 'void') {
            return static function(mixed ...$args) use ($closure, $contextKey, $default): void {
                if (Context::getCurrent()->get($contextKey) ?? $default) {
                    $closure(...$args);
                }
            };
        }

        return static function(mixed ...$args) use ($closure, $contextKey, $default, $return): mixed {
            if (Context::getCurrent()->get($contextKey) ?? $default) {
                return $closure(...$args);
            }

            return $args[$return];
        };
    }
}
