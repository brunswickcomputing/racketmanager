<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;

final class Results_Checker_Data_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Results_Checker_Data::from_object( (object) [
            'id'           => '1',
            'league_id'    => '2',
            'match_id'     => '3',
            'team_id'      => '4',
            'player_id'    => '5',
            'rubber_id'    => '6',
            'description'  => 'Player not registered',
            'status'       => '1',
            'updated_user' => '9',
            'updated_date' => '2026-01-01 10:00:00',
        ] );

        $this->assertSame( 1, $dto->id );
        $this->assertSame( 2, $dto->league_id );
        $this->assertSame( 3, $dto->match_id );
        $this->assertSame( 4, $dto->team_id );
        $this->assertSame( 5, $dto->player_id );
        $this->assertSame( 6, $dto->rubber_id );
        $this->assertSame( 'Player not registered', $dto->description );
        $this->assertSame( 1, $dto->status );
        $this->assertSame( 9, $dto->updated_user );
        $this->assertSame( '2026-01-01 10:00:00', $dto->updated_date );
    }

    public function test_empty_null_and_missing_columns_become_null(): void {
        $dto = Results_Checker_Data::from_object( (object) [ 'match_id' => 3, 'player_id' => '', 'status' => null ] );

        $this->assertNull( $dto->id );
        $this->assertSame( 3, $dto->match_id );
        $this->assertNull( $dto->player_id );
        $this->assertNull( $dto->status );
        $this->assertNull( $dto->description );
        $this->assertNull( $dto->updated_date );
    }

    public function test_named_constructor_is_unchanged(): void {
        $dto = new Results_Checker_Data( id: 7, league_id: 1, match_id: 2, team_id: 3, description: 'x' );

        $this->assertSame( 7, $dto->id );
        $this->assertSame( 'x', $dto->description );
        $this->assertNull( $dto->player_id );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '1', 'league_id' => '2', 'description' => 'd' ];

        $this->assertEquals( Results_Checker_Data::from_object( (object) $data ), Results_Checker_Data::from_array( $data ) );
    }
}
