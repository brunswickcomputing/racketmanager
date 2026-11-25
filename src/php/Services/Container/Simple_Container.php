<?php
declare( strict_types=1 );
namespace Racketmanager\Services\Container;

use InvalidArgumentException;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
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
     * Map of port interfaces to legacy container keys for fallback resolution
     * when unit tests register only the legacy string key on a container.
     *
     * @var array<string, string>
     */
    private const array PORT_LEGACY_FALLBACKS = [
        View_Renderer_Interface::class        => 'view_renderer',
        Club_Service_Interface::class         => 'club_service',
        Team_Service_Interface::class         => 'team_service',
        Player_Service_Interface::class       => 'player_service',
        Registration_Service_Interface::class => 'registration_service',
        Finance_Service_Interface::class      => 'finance_service',
        League_Service_Interface::class       => 'league_service',
        Tournament_Service_Interface::class   => 'tournament_service',
        Event_Repository_Interface::class     => 'event_repository',
        League_Repository_Interface::class    => 'league_repository',
        Fixture_Repository_Interface::class   => 'fixture_repository',
        League_Query_Service_Interface::class => 'league_query_service',
        Event_Query_Service_Interface::class  => 'event_query_service',
        Security_Service_Interface::class     => 'security_service',
        Settings_Service_Interface::class     => 'settings_service',
    ];

    /**
     * Register a service, factory, or alias.
     *
     * @param string                 $id identifier.
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

        if ( isset( $this->aliases[ $id ] ) ) {
            $target_id = $this->resolve_alias( $id );
            unset( $this->instances[ $target_id ] );
            $this->definitions[ $target_id ] = $concrete;
        }

        unset( $this->aliases[ $id ] );
        $this->definitions[ $id ] = $concrete;
        unset( $this->instances[ $id ] );
    }

    /**
     * Register an alias for a target service identifier.
     *
     * @param string $alias alias identifier.
     * @param string $target target identifier.
     *
     * @return void
     * @throws InvalidArgumentException If the alias targets itself or creates a circular alias chain.
     */
    public function alias( string $alias, string $target ): void {
        if ( $alias === $target ) {
            // Translators: %s is the alias identifier.
            throw new InvalidArgumentException( esc_html( sprintf( __( "Cannot alias '%s' to itself", 'racketmanager' ), $alias ) ) );
        }

        // Check for circular alias before registering.
        $current = $target;
        $visited = [ $alias => true ];
        while ( true ) {
            if ( isset( $visited[ $current ] ) ) {
                $cycle = implode( ' -> ', array_keys( $visited ) ) . ' -> ' . $current;
                // Translators: %s is the cycle identifier.
                throw new InvalidArgumentException( esc_html( sprintf( __( 'Circular alias detected: %s', 'racketmanager' ), $cycle ) ) );
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
     * Retrieve a service instance by its identifier.
     *
     * @param string $id identifier.
     *
     * @return object
     * @throws InvalidArgumentException If the service is not defined, a circular dependency is detected, or the service cannot be resolved.
     */
    public function get( string $id ): object {
        $target = $this->resolve_alias( $id );

        if ( isset( $this->instances[ $target ] ) ) {
            return $this->instances[ $target ];
        }

        if ( self::class === $target ) {
            return $this;
        }

        if ( isset( $this->resolving[ $target ] ) ) {
            $chain = implode( ' -> ', array_keys( $this->resolving ) ) . ' -> ' . $target;
            throw new InvalidArgumentException( esc_attr( "Circular dependency detected while resolving '$target' ($chain)" ) );
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
                        $ref          = new ReflectionFunction( $def( ... ) );
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
                throw new InvalidArgumentException( esc_attr( $msg ) );
            }

            // Share instances by default.
            $this->instances[ $target ] = $obj;
            return $obj;
        } finally {
            unset( $this->resolving[ $target ] );
        }
    }

    /**
     * Determine whether a service definition, alias, or instance exists for the given identifier.
     *
     * @param string $id identifier.
     *
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
     * @param string $id identifier.
     *
     * @return bool
     */
    public function is_registered( string $id ): bool {
        return $this->has_definition( $id );
    }

    /**
     * Determine whether the container has a definition, instance, alias, or can autowire an instantiable class for the given identifier.
     *
     * @param string $id identifier.
     *
     * @return bool
     */
    public function has( string $id ): bool {
        if ( isset( $this->instances[ $id ] ) || isset( $this->definitions[ $id ] ) ) {
            return true;
        }

        if ( self::class === $id ) {
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
                return new ReflectionClass( $id )->isInstantiable();
            } catch ( ReflectionException ) {
                return false;
            }
        }

        return false;
    }

    /**
     * Remove a service definition, alias, and any cached instance.
     *
     * @param string $id identifier.
     * @return void
     */
    public function remove( string $id ): void {
        unset( $this->definitions[ $id ], $this->instances[ $id ], $this->aliases[ $id ] );
    }

    /**
     * Resolve alias chains to the canonical service identifier.
     *
     * @param string $id identifier.
     * @return string
     * @throws InvalidArgumentException If a circular alias is detected.
     */
    private function resolve_alias( string $id ): string {
        $visited = [];
        $current = $id;

        while ( isset( $this->aliases[ $current ] ) ) {
            if ( isset( $visited[ $current ] ) ) {
                $cycle = implode( ' -> ', array_keys( $visited ) ) . ' -> ' . $current;
                throw new InvalidArgumentException( esc_attr( "Circular alias detected for '$id': $cycle" ) );
            }
            $visited[ $current ] = true;
            $current             = $this->aliases[ $current ];
        }

        if ( ! isset( $this->definitions[ $current ] ) && ! isset( $this->instances[ $current ] ) && isset( self::PORT_LEGACY_FALLBACKS[ $current ] ) && $this->has( self::PORT_LEGACY_FALLBACKS[ $current ] ) ) {
            return $this->resolve_alias( self::PORT_LEGACY_FALLBACKS[ $current ] );
        }

        return $current;
    }

    /**
     * Autowire an instantiable class via constructor reflection.
     *
     * @param string $class_name class.
     *
     * @return object
     * @throws InvalidArgumentException If the class is not instantiable or dependencies cannot be resolved.
     * @throws Autowire_Unresolvable_Exception If the class is not instantiable or dependencies cannot be resolved.
     */
    private function autowire( string $class_name ): object {
        try {
            $reflector = new ReflectionClass( $class_name );
        } catch ( ReflectionException $e ) {
            throw new Autowire_Unresolvable_Exception( esc_html( "Class '$class_name' could not be reflected: " . $e->getMessage() ), 0, esc_html( $e ) );
        }

        if ( ! $reflector->isInstantiable() ) {
            throw new Autowire_Unresolvable_Exception( esc_html( "Class '$class_name' is not instantiable" ) );
        }

        $constructor  = $reflector->getConstructor();
        $dependencies = [];
        if ( null !== $constructor ) {
            foreach ( $constructor->getParameters() as $param ) {
                $dependencies[] = $this->resolve_parameter( $param, $class_name );
            }
        }

        try {
            return new $class_name( ...$dependencies );
        } catch ( Autowire_Unresolvable_Exception $e ) {
            // Raised from within user constructor code: must not be mistaken for an unresolvable optional parameter.
            throw new InvalidArgumentException( esc_html( $e->getMessage() ), 0, esc_html( $e ) );
        }
    }

    /**
     * Resolve a reflected constructor or method parameter for autowiring.
     *
     * @param ReflectionParameter $param Parameter to resolve.
     * @param string              $class_name Class context used for type normalization and error messages.
     *
     * @return mixed Resolved dependency, default value, or null when allowed.
     * @throws Autowire_Unresolvable_Exception If the parameter cannot be resolved.
     */
    private function resolve_parameter( ReflectionParameter $param, string $class_name ): mixed {
        $param_name = $param->getName();
        $type       = $param->getType();

        if ( null === $type ) {
            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }
            throw new Autowire_Unresolvable_Exception(
                esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name': parameter has no type declaration and no default value" )
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
                    esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name': scalar or built-in type '$type_name' cannot be resolved without a default value" )
                );
            }

            $type_name   = $this->normalize_type_name( $type_name, $class_name );
            $is_optional = $param->isDefaultValueAvailable() || $param->allowsNull();
            $resolved    = $this->resolve_class_candidate( $type_name, $is_optional );

            if ( null !== $resolved ) {
                return $resolved;
            }

            if ( $param->isDefaultValueAvailable() ) {
                return $param->getDefaultValue();
            }

            if ( $param->allowsNull() ) {
                return null;
            }

            throw new Autowire_Unresolvable_Exception(
                esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name': type '$type_name' cannot be resolved" )
            );
        }

        if ( $type instanceof ReflectionUnionType ) {
            foreach ( $type->getTypes() as $sub_type ) {
                if ( $sub_type instanceof ReflectionNamedType && ! $sub_type->isBuiltin() ) {
                    $sub_type_name = $this->normalize_type_name( $sub_type->getName(), $class_name );
                    $resolved      = $this->resolve_class_candidate( $sub_type_name, true );
                    if ( null !== $resolved ) {
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
                esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name': union type cannot be resolved" )
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
                esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name': intersection type cannot be resolved" )
            );
        }

        if ( $param->isDefaultValueAvailable() ) {
            return $param->getDefaultValue();
        }

        if ( $param->allowsNull() ) {
            return null;
        }

        throw new Autowire_Unresolvable_Exception( esc_attr( "Cannot autowire parameter '$param_name' of class '$class_name'" ) );
    }

    /**
     * Resolve a class name to a service instance when available.
     *
     * @param string $class_name Class name to resolve.
     * @param bool   $is_optional Whether resolution may fail without throwing.
     *
     * @return object|null Resolved service instance, or null when it cannot be resolved.
     */
    private function resolve_class_candidate( string $class_name, bool $is_optional ): ?object {
        if ( $this->has_definition( $class_name ) || self::class === $class_name ) {
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
     * Normalise a type name relative to the declaring class context.
     *
     * @param string $type_name Type name to normalize.
     * @param string $declaring_class Class name that declares the type reference.
     *
     * @return string Normalized type name.
     */
    private function normalize_type_name( string $type_name, string $declaring_class ): string {
        if ( 'self' === $type_name || 'static' === $type_name ) {
            return $declaring_class;
        }

        if ( 'parent' === $type_name ) {
            try {
                $parent = new ReflectionClass( $declaring_class )->getParentClass();
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
     * Determine whether a service identifier can be resolved to an available target.
     *
     * @param string $id Service identifier.
     *
     * @return bool True if the identifier resolves to an available service, false otherwise.
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
