<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\File;

use Nevay\OTelSDK\Configuration\Internal\Config\ComponentPlugin;
use Nevay\OTelSDK\Configuration\Internal\Config\ConfigurationFactory;
use function assert;

/**
 * @internal
 */
final class FileMetadata {

    public function __construct(
        public readonly string|array $configFile,
        private readonly ?string $cacheFile,
        private readonly bool $debug,
        private readonly ConfigurationFactory $factory,
    ) {}

    public function parse(): array {
        $plugin = $this->factory->parseFile($this->configFile, $this->cacheFile, $this->debug);
        assert($plugin instanceof ComponentPlugin);

        return $plugin->properties;
    }
}
