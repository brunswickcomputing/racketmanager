<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Container\Autowire_Unresolvable_Exception;
use Racketmanager\Services\Container\Simple_Container;

interface Autowire_Test_Repository_Interface {
    public function get_name(): string;
}

class Autowire_Test_Repository implements Autowire_Test_Repository_Interface {
    public function get_name(): string {
        return 'test_repo';
    }
}

class Autowire_No_Constructor {}

class Autowire_Zero_Params {
    public function __construct() {}
}

class Autowire_With_Dependency {
    public function __construct(
        public Autowire_Test_Repository_Interface $repo
    ) {}
}

class Autowire_Nested_Root {
    public function __construct(
        public Autowire_Nested_Branch $branch
    ) {}
}

class Autowire_Nested_Branch {
    public function __construct(
        public Autowire_Test_Repository_Interface $repo
    ) {}
}

class Autowire_With_Optional_Params {
    public function __construct(
        public string $env = 'production',
        public int $port = 8080,
        public ?string $tag = null
    ) {}
}

class Autowire_With_Nullable_Interface {
    public function __construct(
        public ?Autowire_Test_Repository_Interface $repo = null
    ) {}
}

class Autowire_Cycle_A {
    public function __construct(
        public Autowire_Cycle_B $b
    ) {}
}

class Autowire_Cycle_B {
    public function __construct(
        public Autowire_Cycle_A $a
    ) {}
}

class Autowire_Self_Cycle {
    public function __construct(
        public Autowire_Self_Cycle $self
    ) {}
}

class Autowire_Scalar_Required {
    public function __construct(
        public string $api_key
    ) {}
}

class Autowire_Untyped_Required {
    /**
     * @param mixed $untyped
     */
    public function __construct(
        public $untyped
    ) {}
}

abstract class Autowire_Abstract_Class {
    public function __construct() {}
}

class Autowire_Private_Constructor {
    private function __construct() {}
}

class Autowire_Requires_Aliased_Interface {
    public function __construct(
        public Autowire_Test_Repository_Interface $repo
    ) {}
}

class Autowire_Optional_Aliased_Interface {
    public function __construct(
        public ?Autowire_Test_Repository_Interface $repo = null
    ) {}
}

class Autowire_Opt_Concrete_Needing_Scalar {
    public function __construct(
        public ?Autowire_Scalar_Required $scalar_dep = null
    ) {}
}

class Autowire_Opt_Concrete_With_Default {
    public function __construct(
        public ?Autowire_Scalar_Required $scalar_dep = null,
        public string $tag = 'default'
    ) {}
}

class Autowire_Opt_Cycle {
    public function __construct(
        public ?Autowire_Cycle_A $cycle = null
    ) {}
}

class Autowire_Failing_Service {}

class Autowire_Opt_Failing_Factory {
    public function __construct(
        public ?Autowire_Failing_Service $failing = null
    ) {}
}

class Autowire_Needs_Failing_Service {
    public function __construct(
        public Autowire_Failing_Service $failing
    ) {}
}

class Autowire_Opt_Indirect_Failing_Factory {
    public function __construct(
        public ?Autowire_Needs_Failing_Service $indirect = null
    ) {}
}

class Autowire_Nested_Dep {
    public function __construct(
        public mixed $conn
    ) {}
}

class Autowire_Wrapper_Dep {
    public function __construct(
        public Autowire_Nested_Dep $dep
    ) {}
}

class Autowire_Opt_Wrapper {
    public function __construct(
        public ?Autowire_Wrapper_Dep $wrapper = null
    ) {}
}

class Autowire_User_Ctor_Throws {
    public function __construct() {
        throw new InvalidArgumentException( "Config key 'smtp_host' is not defined" );
    }
}

class Autowire_Opt_User_Ctor_Throws {
    public function __construct(
        public ?Autowire_User_Ctor_Throws $user_ctor = null
    ) {}
}

final class Container_Autowiring_Test extends TestCase {

    public function test_autowires_class_without_constructor(): void {
        $container = new Simple_Container();
        $instance  = $container->get( Autowire_No_Constructor::class );

        $this->assertInstanceOf( Autowire_No_Constructor::class, $instance );
    }

    public function test_autowires_class_with_zero_parameter_constructor(): void {
        $container = new Simple_Container();
        $instance  = $container->get( Autowire_Zero_Params::class );

        $this->assertInstanceOf( Autowire_Zero_Params::class, $instance );
    }

    public function test_autowires_class_with_registered_dependencies(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Test_Repository_Interface::class, fn(): Autowire_Test_Repository => new Autowire_Test_Repository() );

        /** @var Autowire_With_Dependency $service */
        $service = $container->get( Autowire_With_Dependency::class );

        $this->assertInstanceOf( Autowire_With_Dependency::class, $service );
        $this->assertSame( 'test_repo', $service->repo->get_name() );
    }

    public function test_autowires_nested_dependency_tree(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Test_Repository_Interface::class, fn(): Autowire_Test_Repository => new Autowire_Test_Repository() );

        /** @var Autowire_Nested_Root $root */
        $root = $container->get( Autowire_Nested_Root::class );

        $this->assertInstanceOf( Autowire_Nested_Root::class, $root );
        $this->assertInstanceOf( Autowire_Nested_Branch::class, $root->branch );
        $this->assertSame( 'test_repo', $root->branch->repo->get_name() );
    }

    public function test_autowires_and_shares_same_instance(): void {
        $container = new Simple_Container();
        $first     = $container->get( Autowire_No_Constructor::class );
        $second    = $container->get( Autowire_No_Constructor::class );

        $this->assertSame( $first, $second );
    }

    public function test_autowires_with_optional_primitive_parameters(): void {
        $container = new Simple_Container();
        /** @var Autowire_With_Optional_Params $instance */
        $instance = $container->get( Autowire_With_Optional_Params::class );

        $this->assertSame( 'production', $instance->env );
        $this->assertSame( 8080, $instance->port );
        $this->assertNull( $instance->tag );
    }

    public function test_autowires_with_nullable_unregistered_interface_injected_as_null(): void {
        $container = new Simple_Container();
        /** @var Autowire_With_Nullable_Interface $instance */
        $instance = $container->get( Autowire_With_Nullable_Interface::class );

        $this->assertNull( $instance->repo );
    }

    public function test_autowires_with_nullable_registered_interface_injected_as_service(): void {
        $container = new Simple_Container();
        $repo      = new Autowire_Test_Repository();
        $container->set( Autowire_Test_Repository_Interface::class, $repo );

        /** @var Autowire_With_Nullable_Interface $instance */
        $instance = $container->get( Autowire_With_Nullable_Interface::class );

        $this->assertSame( $repo, $instance->repo );
    }

    public function test_throws_on_circular_constructor_dependency(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular dependency detected' );

        $container->get( Autowire_Cycle_A::class );
    }

    public function test_throws_on_self_constructor_dependency(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular dependency detected' );

        $container->get( Autowire_Self_Cycle::class );
    }

    public function test_throws_on_mixed_factory_and_constructor_cycle(): void {
        $container = new Simple_Container();
        $container->set( 'cycle_service', fn( Simple_Container $c ): Autowire_Cycle_A => $c->get( Autowire_Cycle_A::class ) );
        $container->alias( Autowire_Cycle_B::class, 'cycle_service' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular dependency detected' );

        $container->get( Autowire_Cycle_A::class );
    }

    public function test_throws_on_unresolvable_scalar_parameter(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot autowire parameter 'api_key' of class '" . Autowire_Scalar_Required::class . "': scalar or built-in type 'string' cannot be resolved without a default value" );

        $container->get( Autowire_Scalar_Required::class );
    }

    public function test_throws_on_unresolvable_untyped_parameter(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot autowire parameter 'untyped' of class '" . Autowire_Untyped_Required::class . "': parameter has no type declaration and no default value" );

        $container->get( Autowire_Untyped_Required::class );
    }

    public function test_throws_on_unbound_interface(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service '" . Autowire_Test_Repository_Interface::class . "' is not defined" );

        $container->get( Autowire_Test_Repository_Interface::class );
    }

    public function test_throws_on_uninstantiable_abstract_class(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Class '" . Autowire_Abstract_Class::class . "' is not instantiable" );

        $container->get( Autowire_Abstract_Class::class );
    }

    public function test_throws_on_uninstantiable_private_constructor_class(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Class '" . Autowire_Private_Constructor::class . "' is not instantiable" );

        $container->get( Autowire_Private_Constructor::class );
    }

    public function test_shared_instance_identity_across_alias_and_autowired_class(): void {
        $container = new Simple_Container();
        $container->alias( 'alias_no_constructor', Autowire_No_Constructor::class );

        $by_alias = $container->get( 'alias_no_constructor' );
        $by_class = $container->get( Autowire_No_Constructor::class );

        $this->assertSame( $by_alias, $by_class );
    }

    public function test_autowires_required_interface_aliased_to_unregistered_concrete_class(): void {
        $container = new Simple_Container();
        $container->alias( Autowire_Test_Repository_Interface::class, Autowire_Test_Repository::class );

        /** @var Autowire_Requires_Aliased_Interface $instance */
        $instance = $container->get( Autowire_Requires_Aliased_Interface::class );

        $this->assertInstanceOf( Autowire_Requires_Aliased_Interface::class, $instance );
        $this->assertInstanceOf( Autowire_Test_Repository::class, $instance->repo );
        $this->assertSame( 'test_repo', $instance->repo->get_name() );
    }

    public function test_autowires_nullable_interface_aliased_to_unregistered_concrete_class_injects_instance(): void {
        $container = new Simple_Container();
        $container->alias( Autowire_Test_Repository_Interface::class, Autowire_Test_Repository::class );

        /** @var Autowire_Optional_Aliased_Interface $instance */
        $instance = $container->get( Autowire_Optional_Aliased_Interface::class );

        $this->assertInstanceOf( Autowire_Optional_Aliased_Interface::class, $instance );
        $this->assertInstanceOf( Autowire_Test_Repository::class, $instance->repo );
        $this->assertSame( 'test_repo', $instance->repo->get_name() );
    }

    public function test_autowires_required_interface_aliased_to_missing_target_throws_not_defined_exception(): void {
        $container = new Simple_Container();
        $container->alias( Autowire_Test_Repository_Interface::class, 'non_existent_target' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service '" . Autowire_Test_Repository_Interface::class . "' (aliased to 'non_existent_target') is not defined" );

        $container->get( Autowire_Requires_Aliased_Interface::class );
    }

    public function test_autowires_optional_interface_aliased_to_missing_target_fails_loudly(): void {
        $container = new Simple_Container();
        $container->alias( Autowire_Test_Repository_Interface::class, 'non_existent_target' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service '" . Autowire_Test_Repository_Interface::class . "' (aliased to 'non_existent_target') is not defined" );

        $container->get( Autowire_Optional_Aliased_Interface::class );
    }

    public function test_optional_concrete_parameter_falls_back_to_null_when_scalar_fails(): void {
        $container = new Simple_Container();

        /** @var Autowire_Opt_Concrete_Needing_Scalar $instance */
        $instance = $container->get( Autowire_Opt_Concrete_Needing_Scalar::class );

        $this->assertInstanceOf( Autowire_Opt_Concrete_Needing_Scalar::class, $instance );
        $this->assertNull( $instance->scalar_dep );
    }

    public function test_optional_concrete_parameter_falls_back_to_default_value_when_scalar_fails(): void {
        $container = new Simple_Container();

        /** @var Autowire_Opt_Concrete_With_Default $instance */
        $instance = $container->get( Autowire_Opt_Concrete_With_Default::class );

        $this->assertInstanceOf( Autowire_Opt_Concrete_With_Default::class, $instance );
        $this->assertNull( $instance->scalar_dep );
        $this->assertSame( 'default', $instance->tag );
    }

    public function test_optional_parameter_does_not_swallow_circular_dependency(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular dependency detected' );

        $container->get( Autowire_Opt_Cycle::class );
    }

    public function test_optional_parameter_does_not_swallow_registered_factory_exception(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Failing_Service::class, function (): never {
            throw new \RuntimeException( 'Registered factory failure in database driver' );
        } );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Registered factory failure in database driver' );

        $container->get( Autowire_Opt_Failing_Factory::class );
    }

    public function test_optional_parameter_does_not_swallow_indirect_registered_factory_exception(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Failing_Service::class, function (): never {
            throw new \RuntimeException( 'Indirect factory failed' );
        } );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Indirect factory failed' );

        $container->get( Autowire_Opt_Indirect_Failing_Factory::class );
    }

    public function test_optional_parameter_does_not_swallow_nested_registered_factory_invalid_argument_exception(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Nested_Dep::class, function ( Simple_Container $c ): Autowire_Nested_Dep {
            return new Autowire_Nested_Dep( $c->get( 'db_connection' ) );
        } );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service 'db_connection' is not defined" );

        $container->get( Autowire_Opt_Wrapper::class );
    }

    public function test_optional_parameter_does_not_swallow_user_constructor_invalid_argument_exception(): void {
        $container = new Simple_Container();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Config key 'smtp_host' is not defined" );

        $container->get( Autowire_Opt_User_Ctor_Throws::class );
    }

    public function test_optional_parameter_does_not_swallow_nested_factory_cannot_autowire_invalid_argument_exception(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Nested_Dep::class, function (): never {
            throw new InvalidArgumentException( "Cannot autowire parameter 'token' of class 'Some_Class'" );
        } );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot autowire parameter 'token' of class 'Some_Class'" );

        $container->get( Autowire_Opt_Wrapper::class );
    }

    public function test_optional_parameter_does_not_swallow_container_failure_raised_inside_registered_factory(): void {
        $container = new Simple_Container();
        $container->set( Autowire_Boundary_Dep::class, function ( Simple_Container $c ): Autowire_Boundary_Dep {
            return new Autowire_Boundary_Dep( $c->get( Autowire_Boundary_Needs_Scalar::class ) );
        } );

        try {
            $container->get( Autowire_Boundary_Opt_Wrapper::class );
            $this->fail( 'Expected InvalidArgumentException' );
        } catch ( InvalidArgumentException $e ) {
            $this->assertNotInstanceOf( Autowire_Unresolvable_Exception::class, $e );
            $this->assertStringContainsString( 'api_key', $e->getMessage() );
            $this->assertInstanceOf( Autowire_Unresolvable_Exception::class, $e->getPrevious() );
        }
    }

    public function test_optional_parameter_does_not_swallow_container_failure_raised_inside_user_constructor(): void {
        $container = new Simple_Container();

        try {
            $container->get( Autowire_Boundary_Opt_User::class );
            $this->fail( 'Expected InvalidArgumentException' );
        } catch ( InvalidArgumentException $e ) {
            $this->assertNotInstanceOf( Autowire_Unresolvable_Exception::class, $e );
            $this->assertStringContainsString( 'api_key', $e->getMessage() );
            $this->assertInstanceOf( Autowire_Unresolvable_Exception::class, $e->getPrevious() );
        }
    }

    public function test_direct_get_of_unresolvable_class_still_throws_unresolvable_exception(): void {
        $container = new Simple_Container();

        $this->expectException( Autowire_Unresolvable_Exception::class );

        $container->get( Autowire_Boundary_Needs_Scalar::class );
    }

    public function test_array_callable_factory_is_supported_and_invoked_once(): void {
        $maker     = new Autowire_Boundary_Maker();
        $container = new Simple_Container();
        $container->set( 'array_made', [ $maker, 'make' ] );

        $first  = $container->get( 'array_made' );
        $second = $container->get( 'array_made' );

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $maker->calls );
        $this->assertSame( $container, $maker->received );
    }

    public function test_array_callable_factory_without_parameters_is_invoked_once(): void {
        $maker     = new Autowire_Boundary_Maker();
        $container = new Simple_Container();
        $container->set( 'array_made_no_params', [ $maker, 'make_without_container' ] );

        $first  = $container->get( 'array_made_no_params' );
        $second = $container->get( 'array_made_no_params' );

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $maker->calls );
        $this->assertNull( $maker->received );
    }

    public function test_invokable_object_factory_is_supported_and_invoked_once(): void {
        $invokable = new Autowire_Boundary_Invokable();
        $container = new Simple_Container();
        $container->set( 'invokable_made', $invokable );

        $first  = $container->get( 'invokable_made' );
        $second = $container->get( 'invokable_made' );

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $invokable->calls );
        $this->assertSame( $container, $invokable->received );
    }

    public function test_failing_array_callable_factory_is_invoked_only_once(): void {
        $maker     = new Autowire_Boundary_Maker();
        $container = new Simple_Container();
        $container->set( 'array_failing', [ $maker, 'fail' ] );

        try {
            $container->get( 'array_failing' );
            $this->fail( 'Expected RuntimeException' );
        } catch ( \RuntimeException $e ) {
            $this->assertSame( 'array factory failed', $e->getMessage() );
        }

        $this->assertSame( 1, $maker->calls );
    }
}

class Autowire_Boundary_Needs_Scalar {
    public function __construct( public string $api_key ) {}
}

class Autowire_Boundary_Dep {
    public function __construct( public object $inner ) {}
}

class Autowire_Boundary_Wrapper {
    public function __construct( public Autowire_Boundary_Dep $dep ) {}
}

class Autowire_Boundary_Opt_Wrapper {
    public function __construct( public ?Autowire_Boundary_Wrapper $wrapper = null ) {}
}

class Autowire_Boundary_User_Ctor {
    public function __construct( Simple_Container $c ) {
        $c->get( Autowire_Boundary_Needs_Scalar::class );
    }
}

class Autowire_Boundary_Opt_User {
    public function __construct( public ?Autowire_Boundary_User_Ctor $user = null ) {}
}

class Autowire_Boundary_Maker {
    public int $calls        = 0;
    public ?object $received = null;

    public function make( Simple_Container $c ): object {
        ++$this->calls;
        $this->received = $c;
        return new \stdClass();
    }

    public function make_without_container(): object {
        ++$this->calls;
        return new \stdClass();
    }

    public function fail(): never {
        ++$this->calls;
        throw new \RuntimeException( 'array factory failed' );
    }
}

class Autowire_Boundary_Invokable {
    public int $calls        = 0;
    public ?object $received = null;

    public function __invoke( Simple_Container $c ): object {
        ++$this->calls;
        $this->received = $c;
        return new \stdClass();
    }
}
