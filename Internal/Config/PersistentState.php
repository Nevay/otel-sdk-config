<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Config;

use Amp\Cancellation;
use Amp\Future;
use Closure;
use OpenTelemetry\API\Configuration\Config\ComponentPlugin;
use WeakReference;
use function Amp\async;
use function array_key_exists;
use function hash;
use function implode;
use function is_array;
use function method_exists;
use function serialize;

/**
 * @template T
 *
 * @internal
 */
final class PersistentState {

    /**
     * @param array<string, list<string>> $hashes
     * @param array<string, list<T>> $instances
     * @param array<string, array<bool>> $used
     */
    private function __construct(
        private array $hashes = [],
        private array $instances = [],
        private array $used = [],
    ) {}

    public static function load(array $hashes, array $instances): PersistentState {
        $_hashes = [];
        $_instances = [];

        foreach ($hashes as $index => $_) {
            for ($i = 0; $i < count($_); $i++) {
                $ref = $instances[$index][$i];
                if ($instance = $ref->get()) {
                    $_hashes[$index][] = $hashes[$index][$i];
                    $_instances[$index][] = $instance;
                }
            }
        }
        return new self(
            $_hashes,
            $_instances,
        );
    }

    public function export(): array {
        $hashes = [];
        $instances = [];

        foreach ($this->hashes as $index => $_) {
            for ($i = 0; $i < count($_); $i++) {
                if ($this->used[$index][$i] ?? false) {
                    $hashes[$index][] = $this->hashes[$index][$i];
                    $instances[$index][] = WeakReference::create($this->instances[$index][$i]);
                }
            }
        }

        return [
            $hashes,
            $instances,
        ];
    }

    public function shutdown(Cancellation $cancellation, ?Closure $filter = null): bool {
        $futures = [];
        foreach ($this->instances as $index => $instances) {
            foreach ($instances as $i => $instance) {
                if (($this->used[$index][$i] ?? null) || $filter && ($this->used[$index][$i] ?? null) === false) {
                    continue;
                }
                if (!$instance || !method_exists($instance, 'shutdown')) {
                    continue;
                }

                if ($filter && !$filter($instance)) {
                    continue;
                }

                $futures[] = async($instance->shutdown(...), $cancellation);
                $this->instances[$index][$i] = null;
            }
        }

        [$errors, $results] = Future\awaitAll($futures);

        foreach ($errors as $error) {
            throw $error;
        }
        foreach ($results as $success) {
            if (!$success) {
                return false;
            }
        }

        return true;
    }

    public function updatePath(array &$properties, string|int ...$path): void {
        foreach ($path as $segment) {
            if (!array_key_exists($segment, $properties)) {
                return;
            }

            $properties = &$properties[$segment];
        }

        $properties = is_array($properties)
            ? $this->wrapPlugins($properties, $path)
            : $this->wrapPlugin($properties, $path);
    }

    /**
     * @param ComponentPlugin<T>|null $plugin
     * @return ComponentPlugin<T>|null
     */
    public function wrapPlugin(?ComponentPlugin $plugin, array $path): ?ComponentPlugin {
        if (!$plugin) {
            return null;
        }

        return $this->wrapPlugins([$plugin], $path)[0];
    }

    /**
     * @param array<ComponentPlugin<T>> $plugins
     * @return array<ComponentPlugin<T>>
     */
    public function wrapPlugins(array $plugins, array $path): array {
        $index = implode("\0", $path);

        $resolved = [];
        $this->hashes[$index] ??= [];
        $this->instances[$index] ??= [];

        foreach ($plugins as $key => $plugin) {
            $hash = hash('xxh128', serialize($plugin), true);

            for ($i = 0, $n = count($this->hashes[$index] ?? []); $i < $n; $i++) {
                if ($this->hashes[$index][$i] !== $hash || isset($this->used[$index][$i])) {
                    continue;
                }

                $this->used[$index][$i] = false;
                $resolved[$key] = new ReusedPersistentComponentPlugin(
                    $this->instances[$index][$i],
                    $this->used[$index][$i],
                );

                continue 2;
            }

            $this->hashes[$index][$i] = $hash;
            $this->used[$index][$i] = false;
            $resolved[$key] = new PersistingComponentPlugin(
                $plugin,
                $this->instances[$index][$i],
                $this->used[$index][$i],
            );
        }

        return $resolved;
    }
}
