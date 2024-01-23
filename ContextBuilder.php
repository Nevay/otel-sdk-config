<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Amp\DeferredFuture;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;
use Nevay\OTelSDK\Configuration\Logging\BufferingHandler;
use Nevay\OTelSDK\Configuration\Logging\LoggerHandler;
use Nevay\OTelSDK\Metrics\Internal\MeterProvider;
use Psr\Log\LogLevel;
use WeakMap;

/**
 * @internal
 *
 * @experimental
 */
final class ContextBuilder {

    /** @var WeakMap<Context, array{Logger, DeferredFuture<MeterProvider>} */
    private readonly WeakMap $selfDiagnostics;
    private readonly Logger $logger;
    private array $processors = [];

    private function __construct() {
        $this->selfDiagnostics = new WeakMap();
        $this->logger = new Logger('otel');
    }

    public static function create(): self {
        return new ContextBuilder();
    }

    public function pushLogHandler(HandlerInterface $handler): self {
        $this->logger->pushHandler($handler);

        return $this;
    }

    public function pushConfigurationProcessor(ConfigurationProcessor $processor): self {
        $this->processors[] = $processor;

        return $this;
    }

    public function createContext(): Context {
        return new Context(
            logger: clone $this->logger,
            processor: new ConfigurationProcessor\Composite($this->processors),
        );
    }

    public function createSelfDiagnosticsContext(int|Level|string $logLevel = LogLevel::DEBUG): Context {
        $deferredMeterProvider = new DeferredFuture();

        $buffer = new BufferingHandler($logLevel);
        $logger = clone $this->logger;
        $logger->pushHandler($buffer);

        $context = new Context(
            logger: $logger,
            meterProvider: $deferredMeterProvider->getFuture(),
            processor: new ConfigurationProcessor\Composite($this->processors),
        );

        /** @noinspection PhpSecondWriteToReadonlyPropertyInspection */
        $this->selfDiagnostics[$context] = [$logger, $buffer, $deferredMeterProvider];

        return $context;
    }

    public function resolve(Context $context, ConfigurationResult $config): void {
        if (![$logger, $deferredMeterProvider] = $this->selfDiagnostics[$context] ?? null) {
            return;
        }

        unset($this->selfDiagnostics[$context]);

        $deferredMeterProvider->complete($config->meterProvider);

        /** @var BufferingHandler $buffer */
        $buffer = $logger->popHandler();
        $handler = new LoggerHandler($config->loggerProvider, $buffer->getLevel());
        $logger->pushHandler($handler);
        $buffer->replay($handler);
    }
}
