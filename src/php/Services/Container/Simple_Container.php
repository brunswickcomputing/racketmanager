<?php
namespace Racketmanager\Services\Container;

use InvalidArgumentException;

/**
 * Very small service container supporting shared services and lazy factories.
 */
class Simple_Container {
    /** @var array<string, callable|object> */
    private array $definitions = [];
    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Register a service or factory.
     *
     * @param string $id
     * @param callable|object $concrete If callable, it will be invoked as function(Simple_Container $c): object
     * @return void
     */
    public function set(string $id, $concrete): void {
        $this->definitions[$id] = $concrete;
    }

    /**
     * Get a service instance by id.
     *
     * @param string $id
     * @return object
     */
    public function get(string $id): object {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (!isset($this->definitions[$id])) {
            throw new InvalidArgumentException("Service '$id' is not defined");
        }
        $def = $this->definitions[$id];
        $obj = is_callable($def) ? $def($this) : $def;
        // Share instances by default
        $this->instances[$id] = $obj;
        return $obj;
    }

    /**
     * Check if the container has a definition for id.
     */
    public function has(string $id): bool {
        return isset($this->definitions[$id]) || isset($this->instances[$id]);
    }
}
