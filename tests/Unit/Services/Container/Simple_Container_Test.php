<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;

final class Simple_Container_Test extends TestCase {

    public function test_get_instantiates_and_shares_instance(): void {
        $container = new Simple_Container();
        $counter   = 0;

        $container->set( 'my_service', function () use ( &$counter ): stdClass {
            ++$counter;
            $obj        = new stdClass();
            $obj->count = $counter;
            return $obj;
        } );

        $this->assertTrue( $container->has( 'my_service' ) );

        $first  = $container->get( 'my_service' );
        $second = $container->get( 'my_service' );

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $counter );
    }

    public function test_set_clears_cached_instance(): void {
        $container = new Simple_Container();

        $container->set( 'service_a', fn(): stdClass => (object) [ 'version' => 1 ] );
        $first = $container->get( 'service_a' );
        $this->assertSame( 1, $first->version );

        // Override service definition
        $container->set( 'service_a', fn(): stdClass => (object) [ 'version' => 2 ] );
        $second = $container->get( 'service_a' );

        $this->assertNotSame( $first, $second );
        $this->assertSame( 2, $second->version );
    }

    public function test_set_with_direct_instance_clears_previous_cache(): void {
        $container = new Simple_Container();

        $container->set( 'service_b', fn(): stdClass => (object) [ 'name' => 'initial' ] );
        $initial = $container->get( 'service_b' );
        $this->assertSame( 'initial', $initial->name );

        $replacement = (object) [ 'name' => 'replaced' ];
        $container->set( 'service_b', $replacement );

        $this->assertSame( $replacement, $container->get( 'service_b' ) );
    }

    public function test_has_returns_false_for_undefined_service(): void {
        $container = new Simple_Container();
        $this->assertFalse( $container->has( 'non_existent' ) );
    }

    public function test_get_throws_for_undefined_service(): void {
        $container = new Simple_Container();
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service 'unknown_service' is not defined" );

        $container->get( 'unknown_service' );
    }

    public function test_remove_unregisters_definition_and_cached_instance(): void {
        $container = new Simple_Container();
        $container->set( 'ephemeral', fn(): stdClass => (object) [ 'ok' => true ] );

        $this->assertTrue( $container->has( 'ephemeral' ) );
        $this->assertTrue( $container->get( 'ephemeral' )->ok );

        $container->remove( 'ephemeral' );

        $this->assertFalse( $container->has( 'ephemeral' ) );
        $this->expectException( InvalidArgumentException::class );
        $container->get( 'ephemeral' );
    }

    public function test_factory_receives_container_parameter(): void {
        $container = new Simple_Container();
        $container->set( 'dep', fn(): stdClass => (object) [ 'val' => 42 ] );
        $container->set( 'dependent', fn( Simple_Container $c ): stdClass => (object) [
            'dep_val' => $c->get( 'dep' )->val,
        ] );

        $dependent = $container->get( 'dependent' );
        $this->assertSame( 42, $dependent->dep_val );
    }

    public function test_alias_maps_to_target_service(): void {
        $container = new Simple_Container();
        $container->set( 'concrete_service', fn(): stdClass => (object) [ 'value' => 'real' ] );
        $container->alias( 'aliased_service', 'concrete_service' );

        $this->assertTrue( $container->has( 'aliased_service' ) );
        $this->assertSame( 'real', $container->get( 'aliased_service' )->value );
    }

    public function test_alias_shares_identical_instance_with_target(): void {
        $container = new Simple_Container();
        $container->set( 'target', fn(): stdClass => new stdClass() );
        $container->alias( 'alias1', 'target' );
        $container->alias( 'alias2', 'target' );

        $target = $container->get( 'target' );
        $alias1 = $container->get( 'alias1' );
        $alias2 = $container->get( 'alias2' );

        $this->assertSame( $target, $alias1 );
        $this->assertSame( $target, $alias2 );
    }

    public function test_set_with_string_target_registers_alias(): void {
        $container = new Simple_Container();
        $container->set( 'target_svc', fn(): stdClass => (object) [ 'name' => 'target' ] );
        $container->set( 'alias_svc', 'target_svc' );

        $this->assertTrue( $container->has( 'alias_svc' ) );
        $this->assertSame( $container->get( 'target_svc' ), $container->get( 'alias_svc' ) );
    }

    public function test_chained_aliases_resolve_to_same_instance(): void {
        $container = new Simple_Container();
        $container->set( 'base', fn(): stdClass => new stdClass() );
        $container->alias( 'middle', 'base' );
        $container->alias( 'top', 'middle' );

        $base   = $container->get( 'base' );
        $middle = $container->get( 'middle' );
        $top    = $container->get( 'top' );

        $this->assertSame( $base, $middle );
        $this->assertSame( $base, $top );
    }

    public function test_alias_invalidation_when_target_redefined(): void {
        $container = new Simple_Container();
        $container->set( 'target', fn(): stdClass => (object) [ 'version' => 1 ] );
        $container->alias( 'alias_key', 'target' );

        $first = $container->get( 'alias_key' );
        $this->assertSame( 1, $first->version );

        // Redefine target
        $container->set( 'target', fn(): stdClass => (object) [ 'version' => 2 ] );

        $second = $container->get( 'alias_key' );
        $this->assertSame( 2, $second->version );
        $this->assertNotSame( $first, $second );
        $this->assertSame( $second, $container->get( 'target' ) );
    }

    public function test_alias_invalidation_when_alias_redefined_as_concrete(): void {
        $container = new Simple_Container();
        $container->set( 'original_target', fn(): stdClass => (object) [ 'origin' => true ] );
        $container->alias( 'my_key', 'original_target' );

        $this->assertTrue( $container->get( 'my_key' )->origin );

        // Redefine my_key as an independent service
        $container->set( 'my_key', fn(): stdClass => (object) [ 'independent' => true ] );

        $new_obj = $container->get( 'my_key' );
        $this->assertTrue( $new_obj->independent );
        $this->assertFalse( isset( $new_obj->origin ) );
    }

    public function test_remove_clears_alias(): void {
        $container = new Simple_Container();
        $container->set( 'canonical', fn(): stdClass => new stdClass() );
        $container->alias( 'canonical_alias', 'canonical' );

        $this->assertTrue( $container->has( 'canonical_alias' ) );
        $container->remove( 'canonical_alias' );

        $this->assertFalse( $container->has( 'canonical_alias' ) );
        $this->assertTrue( $container->has( 'canonical' ) );
    }

    public function test_alias_self_throws_exception(): void {
        $container = new Simple_Container();
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot alias 'self_key' to itself" );

        $container->alias( 'self_key', 'self_key' );
    }

    public function test_set_string_self_throws_exception(): void {
        $container = new Simple_Container();
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot alias 'self_key' to itself" );

        $container->set( 'self_key', 'self_key' );
    }

    public function test_circular_alias_direct_throws_exception(): void {
        $container = new Simple_Container();
        $container->alias( 'a', 'b' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular alias detected' );

        $container->alias( 'b', 'a' );
    }

    public function test_circular_alias_indirect_throws_exception(): void {
        $container = new Simple_Container();
        $container->alias( 'a', 'b' );
        $container->alias( 'b', 'c' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Circular alias detected' );

        $container->alias( 'c', 'a' );
    }

    public function test_circular_factory_dependency_direct_throws_exception(): void {
        $container = new Simple_Container();
        $container->set( 'service_x', fn( Simple_Container $c ): object => $c->get( 'service_x' ) );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Circular dependency detected while resolving 'service_x'" );

        $container->get( 'service_x' );
    }

    public function test_circular_factory_dependency_indirect_throws_exception(): void {
        $container = new Simple_Container();
        $container->set( 'service_a', fn( Simple_Container $c ): object => $c->get( 'service_b' ) );
        $container->set( 'service_b', fn( Simple_Container $c ): object => $c->get( 'service_a' ) );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Circular dependency detected while resolving 'service_a'" );

        $container->get( 'service_a' );
    }

    public function test_container_resolves_self_instance(): void {
        $container = new Simple_Container();
        $this->assertTrue( $container->has( Simple_Container::class ) );
        $this->assertSame( $container, $container->get( Simple_Container::class ) );
    }

    public function test_has_honors_contract_for_instantiable_classes_before_and_after_get(): void {
        $container = new Simple_Container();

        $this->assertTrue( $container->has( \stdClass::class ) );
        $this->assertFalse( $container->has_definition( \stdClass::class ) );
        $this->assertFalse( $container->is_registered( \stdClass::class ) );

        $instance = $container->get( \stdClass::class );
        $this->assertInstanceOf( \stdClass::class, $instance );

        $this->assertTrue( $container->has( \stdClass::class ) );
        $this->assertTrue( $container->has_definition( \stdClass::class ) );
        $this->assertTrue( $container->is_registered( \stdClass::class ) );
    }

    public function test_has_returns_false_for_unbound_interfaces_and_abstract_classes(): void {
        $container = new Simple_Container();

        $this->assertFalse( $container->has( \Throwable::class ) );
        $this->assertFalse( $container->has_definition( \Throwable::class ) );
    }

    public function test_has_returns_true_for_alias_to_instantiable_class_before_get(): void {
        $container = new Simple_Container();
        $container->alias( 'std_alias', \stdClass::class );

        $this->assertTrue( $container->has( 'std_alias' ) );
        $this->assertTrue( $container->has_definition( 'std_alias' ) );
        $this->assertSame( $container->get( 'std_alias' ), $container->get( \stdClass::class ) );
    }

    public function test_error_message_for_undefined_service_behind_alias_includes_target(): void {
        $container = new Simple_Container();
        $container->alias( 'missing_alias', 'non_existent_service' );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service 'missing_alias' (aliased to 'non_existent_service') is not defined" );

        $container->get( 'missing_alias' );
    }

    public function test_failing_factory_is_invoked_only_once(): void {
        $container   = new Simple_Container();
        $invocations = 0;
        $container->set( 'failing', function () use ( &$invocations ) {
            $invocations++;
            throw new \RuntimeException( 'Factory failed' );
        } );

        try {
            $container->get( 'failing' );
            $this->fail( 'Expected RuntimeException was not thrown' );
        } catch ( \RuntimeException $e ) {
            $this->assertSame( 'Factory failed', $e->getMessage() );
            $this->assertSame( 1, $invocations );
        }
    }

    public function test_has_definition_returns_false_for_unregistered_instantiable_class_with_unautowireable_constructor(): void {
        $container = new Simple_Container();

        $this->assertTrue( $container->has( 'wpdb' ) );
        $this->assertFalse( $container->has_definition( 'wpdb' ) );
        $this->assertFalse( $container->is_registered( 'wpdb' ) );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Cannot autowire parameter 'dbuser' of class 'wpdb'" );

        $container->get( 'wpdb' );
    }
}
