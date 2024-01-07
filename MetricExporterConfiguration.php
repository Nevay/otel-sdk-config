<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration;

use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\MetricExporter;
use Nevay\OtelSDK\Metrics\TemporalityResolver;

final class MetricExporterConfiguration {

    public function __construct(
        public readonly MetricExporter $metricExporter,
        public readonly TemporalityResolver $temporalityResolver,
        public readonly AggregationResolver $aggregationResolver,
    ) {}
}
