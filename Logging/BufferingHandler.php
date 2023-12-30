<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;
use OpenTelemetry\Context\Context;
use function count;

/**
 * @internal
 */
final class BufferingHandler extends AbstractHandler {

    /** @var list<Context> */
    private array $contexts = [];
    /** @var list<array|LogRecord> */
    private array $records = [];

    public function handle(array|LogRecord $record): bool {
        if (!$this->isHandling($record)) {
            return false;
        }

        $this->contexts[] = Context::getCurrent();
        $this->records[] = $record;

        return !$this->bubble;
    }

    public function replay(HandlerInterface $handler): void {
        for ($i = 0, $n = count($this->records); $i < $n; $i++) {
            $scope = $this->contexts[$i]->activate();
            try {
                $handler->handle($this->records[$i]);
            } finally {
                $scope->detach();
            }
        }
    }
}
