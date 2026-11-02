<?php
declare( strict_types=1 );
namespace Racketmanager\Services\Container;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Very small service container supporting shared services, lazy factories, aliases, and autowiring.
 */
class Simple_Container {
    /** @var array<string, callable|object> */
    private array $definitions = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /**
     * Register a service, factory, or alias.
     *
     * @param string $id
     * @param callable|object|string $concrete If callable (closure, [object, method] array or invokable object), it will be invoked as a function(Simple_Container $c): object.
     *                                          If string, it will be registered as an alias to that target
     *                                          (so 'Class::method' string callables are treated as aliases, not factories).
     *                                          If object, it will be registered as a direct shared instance.
     *
     * @return void
     */
    public function set( string $id, callable|object|string $concrete ): void {
        if ( is_string( $concrete ) ) {
            $this->alias( $id, $concrete );
            return;
        }

        unset( $this->aliases[ $id ] );
        $this->definitions[ $id ] = $concrete;
        unset( $this->instances[ $id ] );
    }

    /**
     * Register an alias from one identifier to another.
     *
     * @param string $alias
     * @param string $target
     * @return void
     * @throws InvalidArgumentException If the alias maps directly or transitively to itself.
     */
    public function alias( string $alias, string $target ): void {
        if ( $alias === $target ) {
            throw new InvalidArgumentException( "Cannot alias '$alias' to itself" );
        }

        // Check for circular alias before registering
        $current = $target;
        $visited = [ $alias => true ];
        while ( true ) {
            if ( isset( $visited[ $current ] ) ) {
                $cycle = implode( ' -> ', array_keys( $visited ) ) . ' -> ' . $current;
                throw new InvalidArgumentException( "Circular alias detected: $cycle" );
            }
            if ( ! isset( $this->aliases[ $current ] ) ) {
                break;
            }
            $visited[ $current ] = true;
            $current             = $this->aliases[ $current ];
        }

        unset( $this->definitions[ $alias ], $this->instances[ $alias ] );
        $this->aliases[ $alias ] = $target;
    }

    /**
     * Get a service instance by id.
     *
     * @param string $id
     * @return object
     * @throws InvalidArgumentException
     */
    public function get( string $id ): object {
        $target = $this->resolve_alias( $id );

        if ( isset( $this->instances[ $target ] ) ) {
            return $this->instances[ $target ];
        }

        if ( $target === self::class ) {
            return $this;
        }

        if ( isset( $this->resolving[ $target ] ) ) {
            $chain = implode( ' -> ', array_keys( $this->resolving ) ) . ' -> ' . $target;
            throw new InvalidArgumentException( "Circular dependency detected while resolving '$target' ($chain)" );
        }

        $this->resolving[ $target ] = true;

        try {
            if ( isset( $this->definitions[ $target ] ) ) {
                $def = $this->definitions[ $target ];
                if ( is_callable( $def ) ) {
                    // Backwards-compatible: support both fn(Simple_Container $c) and fn()
                    // to avoid unused-parameter warnings in factories that don't need the container.
                    // Closure::fromCallable() normalises closures, [object, method] arrays and invokable objects.
                    $takes_params = true;
                    try {
                        $ref          = new ReflectionFunction( Closure::fromCallable( $def ) );
                        $takes_params = $ref->getNumberOfParameters() > 0;
                    } catch ( ReflectionException ) {
                        $takes_params = true;
                    }

                    try {
                        $obj = $takes_params ? $def( $this ) : $def();
                    } catch ( Autowire_Unresolvable_Exception $e ) {
                        // Raised from within user code: must not be mistaken for an unresolvable optional parameter.
                        throw new InvalidArgumentException( $e->getMessage(), 0, $e );
                    }
                } else {
                    $obj = $def;
                }
            } elseif ( class_exists( $target ) ) {
                $obj = $this->autowire( $target );
            } else {
                $msg = ( $target !== $id )
                    ? "Service '$id' (aliased to '$target') is not defined"
                    : "Service '$id' is not defined";
                throw new InvalidArgumentException( $msg );
            }

            // Share instances by default
            $this->instances[ $target ] = $obj;
            return $obj;
        } finally {
            unset( $this->resolving[ $target ] );
        }
    }

    /**
     * Check if a service or alias is explicitly registered in definitions,
     * aliases, or instances (excluding dynamic class reflection).
     *
     * @param string $id
     * @return bool
     */
    public function has_definition( string $id ): bool {
        return isset( $this->definitions[ $id ] )
            || isset( $this->aliases[ $id ] )
            || isset( $this->instances[ $id ] );
    }

    /**
     * Alias for has_definition().
     *
     * @param string $id
     * @return bool
     */
    public function is_registered( string $id ): bool {
        return $this->has_definition( $id );
    }

    /**
     * Check if the container has a definition, instance, alias, or can autowire an instantiable class for id.
     *
     * @param string $id
     * @return bool
     */
    public function has( string $id ): bool {
        if ( isset( $this->instances[ $id ] ) || isset( $this->definitions[ $id ] ) ) {
            return true;
        }

        if ( $id === self::class ) {
            return true;
        }

        if ( isset( $this->aliases[ $id ] ) ) {
            try {
                $target = $this->resolve_alias( $id );
                if ( $target !== $id ) {
                    return $this->has( $target );
                }
            } catch ( InvalidArgumentException ) {
                return false;
            }
        }

        if ( class_exists( $id ) ) {
            try {
                return ( new ReflectionClass( $id ) )->isInstantiable();
            } catch ( ReflectionException ) {
                return false;
            }
        }

        return false;
    }

    /**
     * Remove a service definition, alias, and any cached instance.
     *
     * @param string $id
     * @return void
     */
    public function remove( string $id ): void {
        unset( $this->definitions[ $id ], $this->instances[ $id ], $this->aliases[ $id ] );
    }

    /**
     * Resolve alias chains to the canonical service identifier.
     *
     * @param string $id
     * @return string
     * @throws InvalidArgumentException If a circular alias is detected.
     */
    private function resolve_alias( string $id ): string {
        $visited = [];
        $current = $id;

        while ( isset( $this->aliases[ $current ] ) ) {
            if ( isset( $visited[ $current ] ) ) {
                $cycle = implode( ' -> ', array_keys( $visited ) ) . ' -> ' . $current;
                throw new InvalidArgumentException( "Circular alias detected for '$id': $cycle" );
            }
            $visited[ $current ] = true;
            $current             = $this->aliases[ $current ];
        }

        return $current;
    }

    /**
     * Autowire an instantiable class via constructor reflection.
     *
     * @param string $class
     * @return object
     * @throws InvalidArgumentException If the class is not instantiable or dependencies cannot be resolved.
     */
    private function autowire( string $class ): object {
        try {
            $reflector = new ReflectionClass( $class );
        } catch ( ReflectionException $e ) {
            throw new Autowire_Unresolvable_Exception( "Class '$class' could not be reflected: " . $e->getMessage(), 0, $e );
        }

        if ( ! $reflector->isInstantiable() ) {
            throw new Autowire_Unresolvable_Exception( "Class '$class' is not instantiable" );
        }

        $constructor  = $reflector->getConstructor();
        $dependencies = [];
        if ( $constructor !== null ) {
            foreach ( $constructor->getParameters() as $param ) {
                $dependencies[] = $this->resolve_parameter( $param, $class );
            }
        }

        try {
            return new $class( ...$dependencies );
        } catch ( Autowire_Unresolvable_Exception $e ) {
            // Raised from within user constructor code: must not be mistaken for an unresolvable optional parameter.
            throw new InvalidArgumentException( $e->getMessage(), 0, $e );
        }
    }

    /**
     * Resolve a constructor parameter dependency.
     *
     * @param ReflectionParameter $param
     * @param string $class
     * @return mixed
     * @throws InvalidArgumentException If the parameter cannot be resolved.
     */
    private function resolve_parameter( ReflectionParameter $param, string $class ): mixed {
        $param_name = $param->getName();
        $type       = $param->getType();

        if ( $type === null ) {
            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }
            throw new Autowire_Unresolvable_Exception(
                "Cannot autowire parameter '{$param_name}' of class '{$class}': parameter has no type declaration and no default value"
            );
        }

        if ( $type instanceof ReflectionNamedType ) {
            $type_name = $type->getName();

            if ( $type->isBuiltin() ) {
                if ( $param->isDefaultValueAvailable() ) {
                    return $param->getDefaultValue();
                }
                if ( $param->allowsNull() ) {
                    return null;
                }
                throw new Autowire_Unresolvable_Exception(
                    "Cannot autowire parameter '{$param_name}' of class '{$class}': scalar or built-in type '{$type_name}' cannot be resolved without a default value"
                );
            }

            $type_name   = $this->normalize_type_name( $type_name, $class );
            $is_optional = $param->isDefaultValueAvailable() || $param->allowsNull();
            $resolved    = $this->resolve_class_candidate( $type_name, $is_optional );

            if ( $resolved !== null ) {
                return $resolved;
            }

            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }

            if ( $param->allowsNull() ) {
                return null;
            }

            throw new Autowire_Unresolvable_Exception(
                "Cannot autowire parameter '{$param_name}' of class '{$class}': type '{$type_name}' cannot be resolved"
            );
        }

        if ( $type instanceof ReflectionUnionType ) {
            foreach ( $type->getTypes() as $sub_type ) {
                if ( $sub_type instanceof ReflectionNamedType && ! $sub_type->isBuiltin() ) {
                    $sub_type_name = $this->normalize_type_name( $sub_type->getName(), $class );
                    $resolved      = $this->resolve_class_candidate( $sub_type_name, true );
                    if ( $resolved !== null ) {
                        return $resolved;
                    }
                }
            }

            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }

            if ( $param->allowsNull() ) {
                return null;
            }

            throw new Autowire_Unresolvable_Exception(
                "Cannot autowire parameter '{$param_name}' of class '{$class}': union type cannot be resolved"
            );
        }

        if ( $type instanceof ReflectionIntersectionType ) {
            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }

            if ( $param->allowsNull() ) {
                return null;
            }

            throw new Autowire_Unresolvable_Exception(
                "Cannot autowire parameter '{$param_name}' of class '{$class}': intersection type cannot be resolved"
            );
        }

        if ( $param->isDefaultValueAvailable() ) {
            return $param->getDefaultValue();
        }

        if ( $param->allowsNull() ) {
            return null;
        }

        throw new Autowire_Unresolvable_Exception( "Cannot autowire parameter '{$param_name}' of class '{$class}'" );
    }

    /**
     * Resolve a class or interface dependency candidate for a parameter.
     *
     * @param string $class_name
     * @param bool $is_optional
     * @return object|null Resolved object, or null if optional and unresolvable via autowiring.
     * @throws InvalidArgumentException
     */
    private function resolve_class_candidate( string $class_name, bool $is_optional ): ?object {
        if ( $this->has_definition( $class_name ) || $class_name === self::class ) {
            return $this->get( $class_name );
        }

        if ( ! $this->can_resolve( $class_name ) ) {
            return null;
        }

        if ( ! $is_optional ) {
            return $this->get( $class_name );
        }

        try {
            return $this->get( $class_name );
        } catch ( Autowire_Unresolvable_Exception ) {
            return null;
        }
    }

    /**
     * Normalize self/static/parent relative type names to FQCN.
     *
     * @param string $type_name
     * @param string $declaring_class
     * @return string
     */
    private function normalize_type_name( string $type_name, string $declaring_class ): string {
        if ( $type_name === 'self' || $type_name === 'static' ) {
            return $declaring_class;
        }

        if ( $type_name === 'parent' ) {
            try {
                $parent = ( new ReflectionClass( $declaring_class ) )->getParentClass();
                if ( $parent ) {
                    return $parent->getName();
                }
            } catch ( ReflectionException ) {
                return $type_name;
            }
        }

        return $type_name;
    }

    /**
     * Check if the container can resolve an identifier either via explicit registration or autowiring.
     *
     * @param string $id
     * @return bool
     */
    private function can_resolve( string $id ): bool {
        try {
            $target = $this->resolve_alias( $id );
        } catch ( InvalidArgumentException ) {
            return false;
        }

        return $this->has( $target );
    }
}
