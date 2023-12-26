<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\ConfigLoaders\Logs;

use Nevay\OtelSDK\Configuration\Config\Loader;
use Nevay\OtelSDK\Configuration\Config\LoaderRegistry;
use Nevay\OtelSDK\Configuration\Context;
use Nevay\OtelSDK\Logs\LogRecordExporter;
use Nevay\OtelSDK\Otlp\OtlpStreamLogRecordExporter;
use function Amp\ByteStream\getStdout;

/**
 * @implements Loader<LogRecordExporter>
 */
final class LogRecordExporterLoaderConsole implements Loader {

    public function load(array $config, LoaderRegistry $registry, Context $context): LogRecordExporter {
        return new OtlpStreamLogRecordExporter(getStdout(), $context->logger);
    }

    public function type(): string {
        return LogRecordExporter::class;
    }

    public function name(): string {
        return 'console';
    }

    public function dependencies(): array {
        return [
            'tbachert/otel-sdk-otlpexporter' => '^0.1',
            'amphp/byte-stream' => '^2.0',
        ];
    }
}
