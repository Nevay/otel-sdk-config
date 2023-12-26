<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\LogRecord;

/**
 * @internal
 */
final class NoopHandler extends AbstractHandler {

    public function handle(LogRecord $record): bool {
        return !$this->bubble;
    }
}
