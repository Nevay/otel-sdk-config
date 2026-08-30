<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\File;

use Revolt\EventLoop;

/**
 * @internal
 */
final class EventLoopToken {

    public function __construct(
        private readonly string $callbackId,
    ) {}

    public function __destruct() {
        EventLoop::cancel($this->callbackId);
    }
}
