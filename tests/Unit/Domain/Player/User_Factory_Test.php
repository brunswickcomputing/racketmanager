<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\User_Hydration_DTO;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Player\User_Factory;
use TypeError;

final class User_Factory_Test extends TestCase {

    public function test_create_from_dto_mirrors_aliases_onto_wordpress_and_plugin_properties(): void {
        $user = User_Factory::create_from_dto( new User_Hydration_DTO( id: 42, email: 'test@example.com', fullname: 'John Doe', firstname: 'John', user_login: 'jdoe', favourites: [ 'club' => [ 1 ] ] ) );

        $this->assertInstanceOf( User::class, $user );
        $this->assertSame( 42, $user->id );
        $this->assertSame( 42, $user->ID );
        $this->assertSame( 'test@example.com', $user->email );
        $this->assertSame( 'test@example.com', $user->user_email );
        $this->assertSame( 'John Doe', $user->fullname );
        $this->assertSame( 'John Doe', $user->display_name );
        $this->assertSame( 'jdoe', $user->user_login );
        $this->assertSame( 'John', $user->firstname );
        $this->assertSame( [ 1 ], $user->get_favourites( 'club' ) );
    }

    public function test_from_object_accepts_wordpress_shape_like_the_legacy_constructor(): void {
        $user = User_Factory::from_object( (object) [ 'ID' => 42, 'user_email' => 'test@example.com', 'display_name' => 'John Doe' ] );

        $this->assertSame( 42, $user->id );
        $this->assertSame( 42, $user->ID );
        $this->assertSame( 'test@example.com', $user->email );
        $this->assertSame( 'test@example.com', $user->user_email );
        $this->assertSame( 'John Doe', $user->fullname );
        $this->assertSame( 'John Doe', $user->display_name );
        $this->assertFalse( isset( $user->firstname ) );
        $this->assertSame( array(), $user->wtn );
    }

    public function test_from_object_accepts_wp_user_nested_data_and_plugin_shape(): void {
        $wp     = User_Factory::from_object( (object) [ 'ID' => 7, 'data' => (object) [ 'ID' => '7', 'user_email' => 'wp@x.y', 'display_name' => 'WP' ], 'caps' => [], 'roles' => [] ] );
        $plugin = User_Factory::from_object( (object) [ 'id' => 5, 'email' => 'p@x.y', 'fullname' => 'Plugin User' ] );

        $this->assertSame( 7, $wp->ID );
        $this->assertSame( 'wp@x.y', $wp->user_email );
        $this->assertSame( 'WP', $wp->display_name );
        $this->assertFalse( property_exists( $wp, 'caps' ) );
        $this->assertSame( 5, $plugin->id );
        $this->assertSame( 5, $plugin->ID );
        $this->assertSame( 'p@x.y', $plugin->user_email );
        $this->assertSame( 'Plugin User', $plugin->display_name );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'ID' => 3, 'user_email' => 'a@b.c', 'display_name' => 'A', 'age' => 30 ];

        $this->assertEquals( User_Factory::from_object( (object) $data ), User_Factory::from_array( $data ) );
        $this->assertEquals( User_Factory::from_object( (object) $data ), User::from_object( (object) $data ) );
        $this->assertEquals( User::from_object( (object) $data ), User::from_array( $data ) );
        $this->assertSame( 30, User::from_array( $data )->age );
    }

    public function test_empty_constructor_yields_empty_user_and_raw_object_is_rejected(): void {
        $empty = new User();
        $this->assertSame( 0, $empty->ID );
        $this->assertSame( '', $empty->email );
        $this->assertFalse( isset( $empty->id ) );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            User::class,
            new User( (object) [ 'ID' => 1 ] )
        );
    }
}
