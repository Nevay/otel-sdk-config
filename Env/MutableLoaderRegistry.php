<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Env;

use InvalidArgumentException;
use LogicException;
use Nevay\OTelSDK\Configuration\Context;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_map;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class MutableLoaderRegistry implements LoaderRegistry {

    /**
     * @var array<class-string, array<string, Loader>>
     */
    private array $loaders = [];

    public function register(Loader $loader): LoaderRegistry {
        $name = $loader->name();
        $type = self::loadType($loader);
        if (isset($this->loaders[$type][$name])) {
            throw new LogicException(sprintf('Duplicate environment loader registered for "%s" "%s"', $type, $name));
        }

        $this->loaders[$type][$name] ??= $loader;

        return $this;
    }

    public function load(string $type, string $name, EnvResolver $env, Context $context): mixed {
        if (!$loader = $this->loaders[$type][$name] ?? null) {
            throw new InvalidArgumentException(sprintf('Loader for %s %s not found', $type, $name));
        }

        return $loader->load($env, $this, $context);
    }

    public function loadNullable(string $type, ?string $name, EnvResolver $env, Context $context): mixed {
        return $name !== null
            ? $this->load($type, $name, $env, $context)
            : null;
    }

    private static function loadType(Loader $loader): string {
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
