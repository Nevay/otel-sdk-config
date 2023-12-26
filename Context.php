<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Amp\Future;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use Psr\Log\LoggerInterface;

final class Context {

    /**
     * @param LoggerInterface|null $logger
     * @param Future<MeterProviderInterface>|null $meterProvider
     * @param ConfigurationProcessor|null $processor
     */
    public function __construct(
        public readonly ?LoggerInterface $logger = null,
        public readonly ?Future $meterProvider = null,
        public readonly ?ConfigurationProcessor $processor = null,
    ) {}
}
