<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Config;

use Nevay\OTelSDK\Configuration\ConfigurationResult;
use Nevay\OTelSDK\Configuration\Context;

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
