<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\File;

use Closure;
use Revolt\EventLoop;
use function basename;
use function dirname;
use function inotify_add_watch;
use function inotify_init;
use function inotify_read;
use function inotify_rm_watch;
use function restore_error_handler;
use function set_error_handler;
use const IN_CLOSE_WRITE;
use const IN_CREATE;
use const IN_DELETE_SELF;
use const IN_MOVE_SELF;
use const IN_MOVED_TO;
use const IN_ONLYDIR;
use const IN_Q_OVERFLOW;
use const IN_UNMOUNT;

/**
 * @internal
 */
final class InotifyFileWatcher implements FileWatcher {

    public function watch(string $path, Closure $callback, mixed ...$args): object {
        $fd = inotify_init();
        self::registerFileHandler($path, $fd);

        $callbackId = EventLoop::unreference(EventLoop::onReadable($fd, static function($_, $fd) use ($path, $callback, $args): void {
            $triggerCallback = false;
            foreach (inotify_read($fd) as $event) {
                $mask = $event['mask'];

                if ($mask & IN_CLOSE_WRITE) {
                    $triggerCallback = true;
                }
                if ($mask & (IN_DELETE_SELF | IN_UNMOUNT)) {
                    self::registerFileHandler($path, $fd);
                    $triggerCallback = true;
                }
                if ($mask & IN_MOVE_SELF || $mask & (IN_CREATE | IN_MOVED_TO) && $event['name'] === basename($path)) {
                    self::unregisterWatchDescriptor($fd, $event['wd']);
                    self::registerFileHandler($path, $fd);
                    $triggerCallback = true;
                }
                if ($mask & IN_Q_OVERFLOW) {
                    self::registerFileHandler($path, $fd);
                    $triggerCallback = true;
                }
            }

            if ($triggerCallback) {
                EventLoop::queue($callback, ...$args);
            }
        }));

        return new EventLoopToken($callbackId);
    }

    private static function unregisterWatchDescriptor($fd, int $wd): void {
        set_error_handler(static fn() => null);
        try {
            inotify_rm_watch($fd, $wd);
        } finally {
            restore_error_handler();
        }
    }

    private static function registerFileHandler(string $path, $fd): bool {
        set_error_handler(static fn() => null);
        try {
            if (inotify_add_watch($fd, $path, IN_CLOSE_WRITE | IN_MOVE_SELF | IN_DELETE_SELF | IN_UNMOUNT)) {
                return true;
            }

            if (!$wd = inotify_add_watch($fd, dirname($path), IN_ONLYDIR | IN_CREATE | IN_MOVED_TO | IN_MOVE_SELF | IN_DELETE_SELF | IN_UNMOUNT)) {
                throw new \LogicException('Failed to listen on directory');
            }

            if (inotify_add_watch($fd, $path, IN_CLOSE_WRITE | IN_MOVE_SELF | IN_DELETE_SELF | IN_UNMOUNT)) {
                inotify_rm_watch($fd, $wd);
                return true;
            }
        } finally {
            restore_error_handler();
        }

        return false;
    }
}
