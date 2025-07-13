<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\ConfigEnv;

use InvalidArgumentException;
use LogicException;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader;
use OpenTelemetry\API\Configuration\ConfigEnv\EnvResolver;
use OpenTelemetry\API\Configuration\Context;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_map;
use function implode;
use function sprintf;
use function strtolower;

/**
 * @internal
 */
final class EnvComponentLoaderRegistry implements \OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoaderRegistry {

    /** @var array<string, array<string, EnvComponentLoader>> */
    private array $loaders = [];

    public function register(EnvComponentLoader $loader): self {
        $name = $loader->name();
        $type = self::loadType($loader);
        if (isset($this->loaders[$type][$name])) {
            throw new LogicException(sprintf('Duplicate environment loader registered for "%s" "%s"', $type, $name));
        }

        $this->loaders[$type][$name] ??= $loader;

        return $this;
    }

    /**
     * @param class-string $type
     * @param class-string $attribute
     */
    public function loaderHasAttribute(string $type, string $name, string $attribute): bool {
        if (!$loader = $this->loaders[$type][$name] ?? $this->loaders[$type][strtolower($name)] ?? null) {
            return false;
        }

        return (new ReflectionClass($loader))->getAttributes($attribute) !== [];
    }

    public function load(string $type, string $name, EnvResolver $env, Context $context): mixed {
        if (!$loader = $this->loaders[$type][$name] ?? $this->loaders[$type][strtolower($name)] ?? null) {
            throw new InvalidArgumentException(sprintf('Loader for %s %s not found', $type, $name));
        }

        $context->logger->debug('Loading component plugin "{loader}"', ['loader' => $loader::class]);

        return $loader->load($env, $this, $context);
    }

    public function loadAll(string $type, EnvResolver $env, Context $context): iterable {
        foreach ($this->loaders[$type] ?? [] as $loader) {
            yield $loader->load($env, $this, $context);
        }
    }

    private static function loadType(EnvComponentLoader $loader): string {
        if ($returnType = (new ReflectionMethod($loader, 'load'))->getReturnType()) {
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
