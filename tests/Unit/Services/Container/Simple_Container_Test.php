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

    public function test_factory_receives_container_parameter(): void {
        $container = new Simple_Container();
        $container->set( 'dep', fn(): stdClass => (object) [ 'val' => 42 ] );
        $container->set( 'dependent', fn( Simple_Container $c ): stdClass => (object) [
            'dep_val' => $c->get( 'dep' )->val,
        ] );

        $dependent = $container->get( 'dependent' );
        $this->assertSame( 42, $dependent->dep_val );
    }
}
