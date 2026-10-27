<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Domain\Result\Results_Checker_Factory;
use TypeError;

final class Results_Checker_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_fields_and_defaults_missing_ints_to_zero(): void {
        $checker = Results_Checker_Factory::create_from_dto( new Results_Checker_Data( league_id: 2, match_id: 3, team_id: 4, player_id: 5, description: 'Player not registered' ) );

        $this->assertInstanceOf( Results_Checker::class, $checker );
        $this->assertSame( 0, $checker->id );
        $this->assertSame( 2, $checker->league_id );
        $this->assertSame( 3, $checker->match_id );
        $this->assertSame( 4, $checker->team_id );
        $this->assertSame( 5, $checker->player_id );
        $this->assertNull( $checker->rubber_id );
        $this->assertSame( 'Player not registered', $checker->description );
        $this->assertNull( $checker->status );
        $this->assertNull( $checker->updated_date );
        $this->assertNull( $checker->updated_user );
    }

    public function test_from_object_casts_string_db_row_like_the_legacy_std_class_constructor(): void {
        $checker = Results_Checker_Factory::from_object( (object) [
            'id'           => '1',
            'league_id'    => '2',
            'match_id'     => '3',
            'team_id'      => '4',
            'player_id'    => '',
            'rubber_id'    => '5',
            'description'  => 'd',
            'status'       => null,
            'updated_user' => '9',
            'updated_date' => '2026-01-01',
        ] );

        $this->assertSame( 1, $checker->id );
        $this->assertSame( 2, $checker->league_id );
        $this->assertSame( 3, $checker->match_id );
        $this->assertSame( 4, $checker->team_id );
        $this->assertNull( $checker->player_id );
        $this->assertSame( 5, $checker->rubber_id );
        $this->assertSame( 'd', $checker->description );
        $this->assertNull( $checker->status );
        $this->assertSame( 9, $checker->updated_user );
        $this->assertSame( '2026-01-01', $checker->updated_date );

        $sparse = Results_Checker_Factory::from_object( (object) [ 'match_id' => 3 ] );
        $this->assertSame( 0, $sparse->id );
        $this->assertSame( 3, $sparse->match_id );
        $this->assertSame( '', $sparse->description );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '1', 'league_id' => '2', 'match_id' => '3', 'description' => 'd' ];

        $this->assertEquals( Results_Checker_Factory::from_object( (object) $data ), Results_Checker_Factory::from_array( $data ) );
        $this->assertEquals( Results_Checker_Factory::from_object( (object) $data ), Results_Checker::from_object( (object) $data ) );
        $this->assertEquals( Results_Checker::from_object( (object) $data ), Results_Checker::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_checker_and_std_class_is_now_rejected(): void {
        $empty = new Results_Checker();
        $this->assertSame( 0, $empty->id );
        $this->assertSame( '', $empty->description );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Results_Checker::class,
            new Results_Checker( (object) [ 'id' => 1 ] )
        );
    }
}
