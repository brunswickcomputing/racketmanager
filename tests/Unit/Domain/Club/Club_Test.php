<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;
use TypeError;

final class Club_Test extends TestCase {

    public function test_constructor_hydrates_from_dto(): void {
        $dto  = new Club_Hydration_DTO( id: 5, name: 'Direct DTO Club', shortcode: 'DDC' );
        $club = new Club( $dto );

        $this->assertSame( 5, $club->id );
        $this->assertSame( 'Direct DTO Club', $club->name );
        $this->assertSame( 'DDC', $club->shortcode );
    }

    public function test_constructor_without_dto_yields_default_values(): void {
        $club = new Club();

        $this->assertNull( $club->id );
        $this->assertSame( '', $club->name );
        $this->assertNull( $club->shortcode );
        $this->assertNull( $club->founded );
        $this->assertSame( 0, $club->team_count );
        $this->assertSame( 0, $club->player_count );
        $this->assertSame( [], $club->players );
        $this->assertSame( [], $club->teams );
        $this->assertSame( [], $club->competitions );
        $this->assertFalse( $club->single );
        $this->assertNull( $club->match_secretary );
    }

    public function test_constructor_rejects_raw_object(): void {
        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Club::class,
            new Club( (object) [ 'id' => 1, 'name' => 'Raw' ] )
        );
    }
}