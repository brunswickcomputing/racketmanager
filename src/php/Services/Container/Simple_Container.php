<?php
namespace Racketmanager\Services\Container;

use InvalidArgumentException;
use ReflectionFunction;
use Throwable as ThrowableAlias;

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
     * @param callable|object $concrete If callable, it will be invoked as a function(Simple_Container $c): object
     *
     * @return void
     */
    public function set(string $id, callable|object $concrete): void {
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
        if ( is_callable( $def ) ) {
            // Backwards-compatible: support both fn(Simple_Container $c) and fn()
            // to avoid unused-parameter warnings in factories that don't need the container.
            try {
                $ref = new ReflectionFunction( $def );
                $obj = ( $ref->getNumberOfParameters() > 0 ) ? $def( $this ) : $def();
            } catch ( ThrowableAlias ) {
                // Fallback: preserve previous behaviour
                $obj = $def( $this );
            }
        } else {
            $obj = $def;
        }

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
