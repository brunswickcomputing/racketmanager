<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\User_Hydration_DTO;
use Racketmanager\Domain\Player\User;

final class User_Hydration_DTO_Test extends TestCase {

    public function test_wordpress_shape_resolves_id_email_and_display_name(): void {
        $dto = User_Hydration_DTO::from_object( (object) [
            'ID'           => '42',
            'user_email'   => 'test@example.com',
            'display_name' => 'John Doe',
            'user_login'   => 'jdoe',
            'user_pass'    => 'hash',
        ] );

        $this->assertSame( 42, $dto->id );
        $this->assertSame( 'test@example.com', $dto->email );
        $this->assertSame( 'John Doe', $dto->fullname );
        $this->assertSame( 'jdoe', $dto->user_login );
        $this->assertSame( 'hash', $dto->user_pass );
    }

    public function test_wp_user_nested_data_is_read_and_capability_bags_are_dropped(): void {
        $dto = User_Hydration_DTO::from_object( (object) [
            'ID'    => 42,
            'data'  => (object) [ 'ID' => '42', 'user_email' => 'wp@example.com', 'display_name' => 'WP Name', 'user_registered' => '2020-01-01 00:00:00' ],
            'caps'  => [ 'subscriber' => true ],
            'roles' => [ 'subscriber' ],
        ] );

        $this->assertSame( 42, $dto->id );
        $this->assertSame( 'wp@example.com', $dto->email );
        $this->assertSame( 'WP Name', $dto->fullname );
        $this->assertSame( '2020-01-01 00:00:00', $dto->user_registered );
        $this->assertFalse( property_exists( $dto, 'caps' ) );
        $this->assertFalse( property_exists( $dto, 'roles' ) );
        $this->assertFalse( property_exists( $dto, 'data' ) );
    }

    public function test_plugin_shape_resolves_aliases_and_keeps_typed_columns(): void {
        $club = (object) [ 'id' => 3 ];
        $dto  = User_Hydration_DTO::from_object( (object) [
            'id'         => 5,
            'email'      => 'a@b.c',
            'fullname'   => 'A B',
            'favourites' => [ 'club' => [ 1 ] ],
            'firstname'  => 'A',
            'surname'    => 'B',
            'age'        => 30,
            'entry_id'   => '7',
            'club'       => $club,
            'wtn'        => [ 'singles' => 12.5 ],
        ] );

        $this->assertSame( 5, $dto->id );
        $this->assertSame( 'a@b.c', $dto->email );
        $this->assertSame( 'A B', $dto->fullname );
        $this->assertSame( [ 'club' => [ 1 ] ], $dto->favourites );
        $this->assertSame( 'A', $dto->firstname );
        $this->assertSame( 'B', $dto->surname );
        $this->assertSame( 30, $dto->age );
        $this->assertSame( 7, $dto->entry_id );
        $this->assertSame( $club, $dto->club );
        $this->assertSame( [ 'singles' => 12.5 ], $dto->wtn );
    }

    public function test_non_empty_wordpress_key_wins_over_plugin_key(): void {
        $dto = User_Hydration_DTO::from_object( (object) [ 'ID' => 9, 'id' => 1, 'user_email' => 'wp@x.y', 'email' => 'plugin@x.y', 'display_name' => '', 'fullname' => 'Plugin Name' ] );

        $this->assertSame( 9, $dto->id );
        $this->assertSame( 'wp@x.y', $dto->email );
        $this->assertSame( 'Plugin Name', $dto->fullname );
    }

    public function test_empty_object_yields_defaults(): void {
        $dto = User_Hydration_DTO::from_object( (object) [] );

        $this->assertSame( 0, $dto->id );
        $this->assertSame( '', $dto->email );
        $this->assertSame( '', $dto->fullname );
        $this->assertNull( $dto->firstname );
        $this->assertNull( $dto->favourites );
    }

    public function test_from_user_entity_reads_only_initialised_properties(): void {
        $user             = new User();
        $user->id         = 15;
        $user->ID         = 15;
        $user->email      = 'e@x.y';
        $user->user_email = 'e@x.y';
        $user->firstname  = 'F';

        $dto = User_Hydration_DTO::from_object( $user );

        $this->assertSame( 15, $dto->id );
        $this->assertSame( 'e@x.y', $dto->email );
        $this->assertSame( 'F', $dto->firstname );
        $this->assertNull( $dto->surname );
        $this->assertEquals( $dto, User_Hydration_DTO::from_array( [ 'ID' => 15, 'user_email' => 'e@x.y', 'firstname' => 'F', 'matches' => [], 'statistics' => [], 'wtn' => [], 'favourites' => [], 'password' => '', 're_password' => '', 'user_registered' => '' ] ) );
    }
}
