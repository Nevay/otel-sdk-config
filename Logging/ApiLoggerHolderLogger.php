<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Logging;

use OpenTelemetry\API\LoggerHolder;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class ApiLoggerHolderLogger implements LoggerInterface {

    public function emergency($message, array $context = []): void {
        LoggerHolder::get()?->emergency($message, $context);
    }

    public function alert($message, array $context = []): void {
        LoggerHolder::get()?->alert($message, $context);
    }

    public function critical($message, array $context = []): void {
        LoggerHolder::get()?->critical($message, $context);
    }

    public function error($message, array $context = []): void {
        LoggerHolder::get()?->error($message, $context);
    }

    public function warning($message, array $context = []): void {
        LoggerHolder::get()?->warning($message, $context);
    }

    public function notice($message, array $context = []): void {
        LoggerHolder::get()?->notice($message, $context);
    }

    public function info($message, array $context = []): void {
        LoggerHolder::get()?->info($message, $context);
    }

    public function debug($message, array $context = []): void {
        LoggerHolder::get()?->debug($message, $context);
    }

    public function log($level, $message, array $context = []): void {
        LoggerHolder::get()?->log($level, $message, $context);
    }
}
