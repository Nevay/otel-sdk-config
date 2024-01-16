<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use InvalidArgumentException;
use Nevay\OtelSDK\Configuration\Cache\ResourceCollection;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use function error_get_last;
use function extension_loaded;
use function is_string;
use function pathinfo;
use function sprintf;
use const PATHINFO_EXTENSION;

/**
 * @internal
 */
final class YamlExtensionFileLoader extends FileLoader {

    public function __construct(private readonly ?ResourceCollection $resources, FileLocatorInterface $locator, string $env = null) {
        parent::__construct($locator, $env);
    }

    public function load(mixed $resource, string $type = null): mixed {
        assert(extension_loaded('yaml'));

        $path = $this->locator->locate($resource);
        $this->resources?->add(new FileResource($path));

        if (($content = @\yaml_parse_file($path)) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML: %s', $path, error_get_last()['message'] ?? ''));
        }

        return $content;
    }

    public function supports(mixed $resource, string $type = null): bool {
        return extension_loaded('yaml')
            && is_string($resource)
            && match ($type ?? pathinfo($resource, PATHINFO_EXTENSION)) {
                'yaml', 'yml' => true,
                default => false,
            };
    }
}
