<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;

final class Competition_Test_Factory_Test extends TestCase {
    public function test_defaults_create_league_competition_without_seasons(): void {
        $competition = Competition_Test_Factory::create();

        $this->assertSame( 5, $competition->id );
        $this->assertSame( 'Summer League', $competition->name );
        $this->assertSame( Competition_Type::LEAGUE, $competition->type );
        $this->assertSame( 0, $competition->seasons->count() );
    }

    public function test_list_seasons_are_passed_through_without_rekeying(): void {
        $competition = Competition_Test_Factory::create( seasons: Competition_Test_Factory::season_list() );

        $this->assertSame( 1, $competition->seasons->count() );
        $this->assertNull( $competition->seasons->get( '2026' ) );
    }

    public function test_map_seasons_are_keyed_by_season_name(): void {
        $competition = Competition_Test_Factory::create( seasons: Competition_Test_Factory::season_map( '2026', [ 'num_courts' => 4 ] ) );

        $this->assertSame( 'array', gettype( $competition->seasons->get( '2026' ) ) );
        $this->assertSame( 4, $competition->seasons->get( '2026' )['num_courts'] );
    }

    public function test_current_season_is_only_set_when_requested(): void {
        $season      = [ 'name' => '2026', 'venue' => null ];
        $without     = Competition_Test_Factory::create( seasons: [ $season ] );
        $with        = Competition_Test_Factory::create( seasons: [ $season ], current_season: $season );

        $this->assertNotSame( $season, $without->current_season );
        $this->assertSame( $season, $with->current_season );
    }

    public function test_cup_type_keeps_generated_finals_unless_overridden(): void {
        $generated  = Competition_Test_Factory::create( type: Competition_Type::CUP );
        $overridden = Competition_Test_Factory::create( type: Competition_Type::CUP, finals: [ [ 'round' => 1, 'name' => 'Final' ] ] );

        $this->assertTrue( $generated->is_cup );
        $this->assertNotEmpty( $generated->finals );
        $this->assertSame( [ [ 'round' => 1, 'name' => 'Final' ] ], $overridden->finals );
    }

    public function test_settings_are_applied_when_given(): void {
        $competition = Competition_Test_Factory::create( settings: [ 'fixed_match_dates' => false ] );

        $this->assertFalse( $competition->get_settings()['fixed_match_dates'] ?? true );
    }
}
