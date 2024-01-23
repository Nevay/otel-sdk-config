<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\LogRecord;

/**
 * @internal
 */
final class NoopHandler extends AbstractHandler {

    public function handle(array|LogRecord $record): bool {
        return !$this->bubble;
    }
}
