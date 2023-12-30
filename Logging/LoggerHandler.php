<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use Nevay\OtelSDK\Common\StackTrace;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\Map\Psr3;
use Psr\Log\LogLevel;
use Throwable;

/**
 * @internal
 */
final class LoggerHandler extends AbstractHandler {

    private readonly LoggerProviderInterface $loggerProvider;

    public function __construct(LoggerProviderInterface $loggerProvider, int|string|Level $level = LogLevel::DEBUG, bool $bubble = true) {
        parent::__construct($level, $bubble);
        $this->loggerProvider = $loggerProvider;
    }

    public function handle(array|LogRecord $record): bool {
        if (!$this->isHandling($record)) {
            return false;
        }

        $formatted = (new NormalizerFormatter())->format($record);
        $logRecord = new \OpenTelemetry\API\Logs\LogRecord();
        $logRecord
            ->setTimestamp((int) $record['datetime']->format('Uu') * 1000)
            ->setSeverityNumber(Psr3::severityNumber($record['level_name']))
            ->setSeverityText($record['level_name'])
            ->setBody($formatted['message'])
        ;
        $exception = $record['context']['exception'] ?? null;
        if ($exception instanceof Throwable) {
            $logRecord
                ->setAttribute('exception.type', $exception::class)
                ->setAttribute('exception.message', $exception->getMessage())
                ->setAttribute('exception.stacktrace', StackTrace::format($exception, StackTrace::DOT_SEPARATOR))
            ;
            unset($formatted['context']['exception']);
        }

        $logRecord->setAttribute('monolog.context', Utils::jsonEncode($formatted['context'], ignoreErrors: true));
        $logRecord->setAttribute('monolog.extra', Utils::jsonEncode($formatted['extra'], ignoreErrors: true));

        $this->loggerProvider->getLogger($record['channel'])->emit($logRecord);

        return !$this->bubble;
    }
}
