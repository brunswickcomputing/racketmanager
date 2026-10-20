<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

final class Player_Test_Factory_Test extends TestCase {
    public function test_defaults_create_player_with_semantic_defaults(): void {
        $player = Player_Test_Factory::create();

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 1, $player->id );
        $this->assertSame( 'Test Player', $player->display_name );
        $this->assertSame( '12345678', $player->btm );
        $this->assertSame( 'Test', $player->firstname );
        $this->assertSame( 'Player', $player->surname );
        $this->assertSame( 'player@example.test', $player->email );
        $this->assertSame( 'M', $player->gender );
        $this->assertSame( 'Test Player', $player->name );
        $this->assertSame( '/player/test-player/12345678/', $player->link );
        $this->assertSame( 1, $player->ID );
        $this->assertSame( 'Test Player', $player->fullname );

        $player->id = 2;
        $this->assertSame( 2, $player->ID );

        $player->display_name = 'Updated Player';
        $this->assertSame( 'Updated Player', $player->fullname );
    }

    public function test_create_with_custom_attributes(): void {
        $player = Player_Test_Factory::create(
            id: 42,
            display_name: 'Jane Doe',
            btm: '10203040',
            firstname: 'Jane',
            surname: 'Doe',
            email: 'jane@example.test',
            gender: 'F'
        );

        $this->assertSame( 42, $player->id );
        $this->assertSame( 'Jane Doe', $player->display_name );
        $this->assertSame( '10203040', $player->btm );
        $this->assertSame( 'Jane', $player->firstname );
        $this->assertSame( 'Doe', $player->surname );
        $this->assertSame( 'jane@example.test', $player->email );
        $this->assertSame( 'F', $player->gender );
    }

    public function test_create_with_null_attributes(): void {
        $player = Player_Test_Factory::create(
            id: null,
            display_name: null,
            btm: null,
            firstname: null,
            surname: null,
            email: null,
            gender: null
        );

        $this->assertNull( $player->id );
        $this->assertNull( $player->display_name );
        $this->assertNull( $player->btm );
        $this->assertNull( $player->firstname );
        $this->assertNull( $player->surname );
        $this->assertNull( $player->email );
        $this->assertNull( $player->gender );
        $this->assertNull( $player->ID );
        $this->assertNull( $player->fullname );
        $this->assertSame( '', $player->name );
        $this->assertSame( '', $player->link );
    }

    public function test_create_with_overrides(): void {
        $player = Player_Test_Factory::create(
            overrides: [
                'locked' => true,
                'wtn'    => [ 'singles' => '10.5' ],
            ]
        );

        $this->assertTrue( $player->locked );
        $this->assertSame( [ 'singles' => '10.5' ], $player->wtn );
    }

    public function test_hydrates_display_name_and_btm_overrides_before_deriving_fields(): void {
        $player = Player_Test_Factory::create(
            overrides: [
                'display_name' => 'Ada Lovelace',
                'btm'          => '87654321',
            ]
        );

        $this->assertSame( 'Ada Lovelace', $player->display_name );
        $this->assertSame( 'Ada Lovelace', $player->name );
        $this->assertSame( '87654321', $player->btm );
        $this->assertSame( '/player/ada-lovelace/87654321/', $player->link );
        $this->assertSame( 'Ada Lovelace', $player->fullname );
    }

    public function test_create_sparse_preserves_unspecified_identity_fields(): void {
        $player = Player_Test_Factory::create_sparse( [ 'btm' => '12345678' ] );

        $this->assertNull( $player->id );
        $this->assertNull( $player->display_name );
        $this->assertSame( '', $player->name );
        $this->assertSame( '', $player->link );
        $this->assertSame( '12345678', $player->btm );
    }

    public function test_rejects_unknown_property_overrides(): void {
        $this->expectException( \InvalidArgumentException::class );

        Player_Test_Factory::create( overrides: [ 'unknown_property' => true ] );
    }
}
