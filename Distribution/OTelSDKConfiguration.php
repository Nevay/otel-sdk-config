<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Distribution;

use Nevay\OTelSDK\Trace\SpanSuppression\NoopSuppressionStrategy;
use Nevay\OTelSDK\Trace\SpanSuppressionStrategy;

final class OTelSDKConfiguration implements DistributionConfiguration {

    public function __construct(
        public readonly ?float $shutdownTimeout = null,
        public readonly SpanSuppressionStrategy $spanSuppressionStrategy = new NoopSuppressionStrategy(),
    ) {}
}
