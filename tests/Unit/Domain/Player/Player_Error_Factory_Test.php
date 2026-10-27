<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Error_Hydration_DTO;
use Racketmanager\Domain\Player\Player_Error;
use Racketmanager\Domain\Player\Player_Error_Factory;

final class Player_Error_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_every_field(): void {
        $error = Player_Error_Factory::create_from_dto( new Player_Error_Hydration_DTO( id: 3, player_id: 4, message: 'WTN lookup failed', created_date: '2026-01-01 00:00:00' ) );

        $this->assertInstanceOf( Player_Error::class, $error );
        $this->assertSame( 3, $error->get_id() );
        $this->assertSame( 4, $error->get_player_id() );
        $this->assertSame( 'WTN lookup failed', $error->get_message() );
        $this->assertSame( '2026-01-01 00:00:00', $error->get_created_date() );
    }

    public function test_from_object_casts_string_db_row_and_keeps_missing_id_null(): void {
        $error = Player_Error_Factory::from_object( (object) [ 'player_id' => '4', 'message' => 'm', 'created_date' => 'd' ] );

        $this->assertNull( $error->get_id() );
        $this->assertSame( 4, $error->get_player_id() );
        $this->assertSame( 'm', $error->get_message() );
        $this->assertSame( 'd', $error->get_created_date() );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '1', 'player_id' => '9', 'message' => 'm', 'created_date' => 'd' ];

        $this->assertEquals( Player_Error_Factory::from_object( (object) $data ), Player_Error_Factory::from_array( $data ) );
        $this->assertEquals( Player_Error_Factory::from_object( (object) $data ), Player_Error::from_object( (object) $data ) );
        $this->assertEquals( Player_Error::from_object( (object) $data ), Player_Error::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_error_and_raw_object_is_rejected(): void {
        $empty = new Player_Error();
        $this->assertNull( $empty->get_id() );
        $this->assertFalse( isset( $empty->message ) );

        $this->expectException( \TypeError::class );
        new Player_Error( (object) [ 'id' => 1 ] );
    }
}
