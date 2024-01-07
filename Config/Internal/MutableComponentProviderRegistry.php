<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Configuration\Config\Internal;

use LogicException;
use Nevay\OtelSDK\Configuration\Config\ComponentProvider;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderDependency;
use Nevay\OtelSDK\Configuration\Config\ComponentProviderRegistry;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_map;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class MutableComponentProviderRegistry implements ComponentProviderRegistry {

    /** @var array<string, array<string, ComponentProvider>> */
    private array $providers = [];

    public function register(ComponentProvider $provider): void {
        $reflection = new ReflectionClass($provider);

        /** @var ReflectionAttribute<ComponentProviderDependency> $attribute */
        foreach ($reflection->getAttributes(ComponentProviderDependency::class) as $attribute) {
            if (!$attribute->newInstance()->isSatisfied()) {
                return;
            }
        }

        $name = $provider->getConfig(new MutableComponentProviderRegistry())->getNode(true)->getName();
        $type = self::loadType($reflection);
        if (isset($this->providers[$type][$name])) {
            throw new LogicException(sprintf('Duplicate component provider registered for "%s" "%s"', $type, $name));
        }

        $this->providers[$type][$name] = $provider;
    }

    public function getProviders(string $type): array {
        return $this->providers[$type] ?? [];
    }

    private static function loadType(ReflectionClass $reflection): string {
        if ($returnType = $reflection->getMethod('createPlugin')->getReturnType()) {
            return self::typeToString($returnType);
        }

        return 'mixed';
    }

    private static function typeToString(ReflectionType $type): string {
        return match ($type::class) {
            ReflectionNamedType::class => $type->getName(),
            ReflectionUnionType::class => implode('|', array_map(self::typeToString(...), $type->getTypes())),
            ReflectionIntersectionType::class => implode('&', array_map(self::typeToString(...), $type->getTypes())),
        };
    }
}
