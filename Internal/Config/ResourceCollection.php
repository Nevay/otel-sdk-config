<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\Config\Resource\ComposerResource;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use function array_values;
use function realpath;
use function str_starts_with;
use function strlen;
use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
final class ResourceCollection {

    /** @var array<ResourceInterface> */
    private array $resources = [];
    private ComposerResource $composerResource;
    /** @var list<string> */
    private array $vendors;

    public function addClassResource(object|string $class): void {
        try {
            $reflection = new ReflectionClass($class);
            if ($this->isInVendors($reflection->getFileName())) {
                return;
            }

            $this->addResource(new ReflectionClassResource($reflection, $this->vendors));
        } catch (ReflectionException) {
            $this->addResource(new ClassExistenceResource($class, false));
        }
    }

    public function addResource(ResourceInterface $resource): void {
        $path = match (true) {
            $resource instanceof FileResource => $resource->getResource(),
            $resource instanceof GlobResource => $resource->getPrefix(),
            $resource instanceof DirectoryResource => $resource->getResource(),
            default => null,
        };

        if ($path !== null && $this->isInVendors($path)) {
            return;
        }

        $this->resources[(string) $resource] = $resource;
    }

    /**
     * @return list<ResourceInterface>
     */
    public function toArray(): array {
        return array_values($this->resources);
    }

    /**
     * @see ReflectionClassResource::loadFiles()
     */
    private function isInVendors(string $path): bool {
        $path = realpath($path) ?: $path;

        $this->composerResource ??= new ComposerResource();
        $this->vendors ??= $this->composerResource->getVendors();

        foreach ($this->vendors as $vendor) {
            $c = $path[strlen($vendor)] ?? null;
            if (str_starts_with($path, $vendor) && ($c === '/' || $c === DIRECTORY_SEPARATOR)) {
                $this->addResource($this->composerResource);
                return true;
            }
        }

        return false;
    }
}
