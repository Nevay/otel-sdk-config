<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\File;

use Closure;
use Revolt\EventLoop;

/**
 * @internal
 */
final class PeriodicFileWatcher implements FileWatcher {

    public function __construct(
        private readonly float $interval,
    ) {}

    public function watch(string $path, Closure $callback, mixed ...$args): object {
        $callbackId = EventLoop::unreference(EventLoop::repeat($this->interval, static function() use ($callback, $args) {
            EventLoop::queue($callback, ...$args);
        }));

        return new EventLoopToken($callbackId);
    }
}
