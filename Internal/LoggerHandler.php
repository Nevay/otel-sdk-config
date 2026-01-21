<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\Severity;
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

        $logger = $this->loggerProvider->getLogger($record['channel']);
        $severity = Severity::fromPsr3($record['level'])->value;

        if (!$logger->isEnabled(severityNumber: $severity)) {
            return false;
        }

        $formatted = (new NormalizerFormatter())->format($record);
        $logRecord = $logger->logRecordBuilder();
        $logRecord
            ->setTimestamp((int) $record['datetime']->format('Uu') * 1000)
            ->setSeverityNumber($severity)
            ->setSeverityText($record['level_name'])
            ->setBody($formatted['message'])
        ;
        $exception = $record['context']['exception'] ?? null;
        if ($exception instanceof Throwable) {
            $logRecord->setException($exception);
            unset($formatted['context']['exception']);
        }

        $logRecord->setAttribute('monolog.context', Utils::jsonEncode($formatted['context'], ignoreErrors: true));
        $logRecord->setAttribute('monolog.extra', Utils::jsonEncode($formatted['extra'], ignoreErrors: true));

        $logRecord->emit();

        return !$this->bubble;
    }
}
