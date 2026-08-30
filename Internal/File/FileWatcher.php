<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\File;

use Closure;

/**
 * @internal
 */
interface FileWatcher {

    public function watch(string $path, Closure $callback): object;
}
