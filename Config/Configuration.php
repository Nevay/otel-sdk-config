<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config;

use Nevay\OtelSDK\Configuration\ConfigurationResult;
use Nevay\OtelSDK\Configuration\Context;

final class Configuration {

    /**
     * @param ComponentPlugin<ConfigurationResult> $configuration
     *
     * @internal
     */
    public function __construct(
        private readonly ComponentPlugin $configuration,
    ) {}

    /**
     * @param Context $context context to use for component creation
     * @return ConfigurationResult created SDK components
     */
    public function create(Context $context): ConfigurationResult {
        return $this->configuration->create($context);
    }
}
