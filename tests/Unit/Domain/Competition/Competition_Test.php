<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

/**
 * Unit tests for Competition domain aggregate encapsulation, lifecycle, and mutation methods.
 */
final class Competition_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_competition( array $seasons = [], array $settings = [] ): Competition {
        return Competition_Factory::create_from_dto(
            new Competition_Hydration_DTO(
                id: 1,
                name: 'Premier League',
                type: Competition_Type::LEAGUE,
                age_group: 'Adults',
                seasons: $seasons,
                settings: $settings
            )
        );
    }

    public function test_public_properties_are_readable(): void {
        $competition = $this->create_competition();

        $this->assertSame( 1, $competition->id );
        $this->assertSame( 'Premier League', $competition->name );
        $this->assertSame( Competition_Type::LEAGUE, $competition->type );
        $this->assertSame( 'Adults', $competition->age_group );
        $this->assertIsArray( $competition->finals );
        $this->assertIsArray( $competition->standings );
        $this->assertNull( $competition->entry_link );
        $this->assertFalse( $competition->is_cup );
        $this->assertTrue( $competition->is_league );
    }

    public function test_external_mutation_of_private_set_property_throws_error(): void {
        $competition = $this->create_competition();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        // Direct write from outside class scope must fail at PHP language level.
        $competition->entry_link = 'https://example.com';
    }

    public function test_external_mutation_of_finals_throws_error(): void {
        $competition = $this->create_competition();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $competition->finals = [ 'final' => 1 ];
    }

    public function test_external_mutation_of_teams_throws_error(): void {
        $competition = $this->create_competition();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $competition->teams = [ 1, 2, 3 ];
    }

    public function test_season_lifecycle_open_season_transitions_state(): void {
        $seasons = [
            '2026' => [
                'name'           => '2026',
                'date_open'      => '2026-01-01',
                'date_closing'   => '2026-02-01',
                'date_start'     => '2026-03-01',
                'date_end'       => '2026-06-01',
                'num_match_days' => 12,
            ],
        ];

        $competition = $this->create_competition( $seasons );
        $competition->open_season( '2026' );

        $this->assertSame( 'open', $competition->current_phase );
        $this->assertTrue( $competition->is_open );
        $this->assertFalse( $competition->is_closed );
        $this->assertFalse( $competition->is_started );
        $this->assertFalse( $competition->is_complete );
        $this->assertFalse( $competition->is_pending );
        $this->assertSame( 12, $competition->num_match_days );
        $this->assertSame( '2026', $competition->current_season['name'] );
    }

    public function test_season_lifecycle_close_season_transitions_state(): void {
        $seasons = [
            '2026' => [
                'name'           => '2026',
                'num_match_days' => 10,
            ],
        ];

        $competition = $this->create_competition( $seasons );
        $competition->close_season( '2026' );

        $this->assertSame( 'close', $competition->current_phase );
        $this->assertTrue( $competition->is_closed );
        $this->assertFalse( $competition->is_open );
        $this->assertFalse( $competition->is_started );
        $this->assertFalse( $competition->is_complete );
        $this->assertFalse( $competition->is_pending );
        $this->assertSame( 10, $competition->num_match_days );
    }

    public function test_season_lifecycle_start_season_transitions_state(): void {
        $seasons = [
            '2026' => [
                'name'           => '2026',
                'num_match_days' => 8,
            ],
        ];

        $competition = $this->create_competition( $seasons );
        $competition->start_season( '2026' );

        $this->assertSame( 'start', $competition->current_phase );
        $this->assertTrue( $competition->is_started );
        $this->assertFalse( $competition->is_open );
        $this->assertFalse( $competition->is_closed );
        $this->assertFalse( $competition->is_complete );
        $this->assertFalse( $competition->is_pending );
    }

    public function test_season_lifecycle_complete_season_transitions_state(): void {
        $seasons = [
            '2026' => [
                'name'           => '2026',
                'num_match_days' => 14,
            ],
        ];

        $competition = $this->create_competition( $seasons );
        $competition->complete_season( '2026' );

        $this->assertSame( 'complete', $competition->current_phase );
        $this->assertTrue( $competition->is_complete );
        $this->assertFalse( $competition->is_open );
        $this->assertFalse( $competition->is_closed );
        $this->assertFalse( $competition->is_started );
        $this->assertFalse( $competition->is_pending );
    }

    public function test_set_current_phase_synchronizes_flags(): void {
        $competition = $this->create_competition();

        $competition->set_current_phase( 'end' );
        $this->assertSame( 'end', $competition->current_phase );
        $this->assertTrue( $competition->is_complete );

        $competition->set_current_phase( 'pending' );
        $this->assertSame( 'pending', $competition->current_phase );
        $this->assertTrue( $competition->is_pending );
        $this->assertFalse( $competition->is_complete );

        $competition->set_current_phase( null );
        $this->assertNull( $competition->current_phase );
        $this->assertFalse( $competition->is_complete );
        $this->assertFalse( $competition->is_started );
        $this->assertFalse( $competition->is_closed );
        $this->assertFalse( $competition->is_open );
        $this->assertFalse( $competition->is_pending );
    }

    public function test_child_and_relation_setters(): void {
        $competition = $this->create_competition();

        $finals = [ 'semi' => [ 1, 2 ] ];
        $competition->set_finals( $finals );
        $this->assertSame( $finals, $competition->finals );

        $events = [ (object) [ 'id' => 10 ] ];
        $competition->set_events( $events );
        $this->assertSame( $events, $competition->events );

        $teams = [ (object) [ 'id' => 20 ] ];
        $competition->set_teams( $teams );
        $this->assertSame( $teams, $competition->teams );

        $players = [ (object) [ 'id' => 30 ] ];
        $competition->set_players( $players );
        $this->assertSame( $players, $competition->players );

        $player = (object) [ 'id' => 40, 'name' => 'Alice' ];
        $competition->set_player( $player );
        $this->assertSame( $player, $competition->player );

        $clubs = [ (object) [ 'id' => 50 ] ];
        $competition->set_clubs( $clubs );
        $this->assertSame( $clubs, $competition->clubs );
    }

    public function test_configuration_and_state_modifiers(): void {
        $competition = $this->create_competition();

        $competition->set_entry_link( 'https://racketmanager.local/enter' );
        $this->assertSame( 'https://racketmanager.local/enter', $competition->entry_link );

        $competition->set_entry_link( null );
        $this->assertNull( $competition->entry_link );

        $competition->set_type( Competition_Type::CUP );
        $this->assertSame( Competition_Type::CUP, $competition->type );
        $this->assertTrue( $competition->is_cup );
        $this->assertFalse( $competition->is_league );

        $competition->set_type( 'tournament' );
        $this->assertSame( Competition_Type::TOURNAMENT, $competition->type );
        $this->assertTrue( $competition->is_tournament );
        $this->assertTrue( $competition->is_player_entry );
        $this->assertFalse( $competition->is_team_entry );

        $competition->set_standings( [ 'status' => 0 ] );
        $this->assertSame( [ 'status' => 0 ], $competition->standings );

        $competition->set_entry_type( 'player' );
        $this->assertSame( 'player', $competition->entry_type );

        $competition->set_mode( 'championship' );
        $this->assertSame( 'championship', $competition->mode );
        $this->assertTrue( $competition->is_championship );

        $competition->set_sport( 'badminton' );
        $this->assertSame( 'badminton', $competition->sport );

        $competition->set_default_match_start_time( [ 'hour' => 18, 'minutes' => 0 ] );
        $this->assertSame( [ 'hour' => 18, 'minutes' => 0 ], $competition->default_match_start_time );

        $competition->set_point_rule( 'squash' );
        $this->assertSame( 'squash', $competition->point_rule );

        $competition->set_point_format( '%s' );
        $this->assertSame( '%s', $competition->point_format );

        $competition->set_point_2_format( '%s / %s' );
        $this->assertSame( '%s / %s', $competition->point_2_format );

        $competition->set_team_ranking( 'manual' );
        $this->assertSame( 'manual', $competition->team_ranking );

        $competition->set_is_active( true );
        $this->assertTrue( $competition->is_active );

        $competition->set_is_open( true );
        $this->assertTrue( $competition->is_open );

        $competition->set_is_closed( true );
        $this->assertTrue( $competition->is_closed );

        $competition->set_is_started( true );
        $this->assertTrue( $competition->is_started );

        $competition->set_is_complete( true );
        $this->assertTrue( $competition->is_complete );

        $competition->set_is_pending( true );
        $this->assertTrue( $competition->is_pending );
    }

    public function test_count_and_offset_setters(): void {
        $competition = $this->create_competition();

        $competition->set_num_matches_per_page( 25 );
        $this->assertSame( 25, $competition->num_matches_per_page );

        $competition->set_num_match_days( 16 );
        $this->assertSame( 16, $competition->num_match_days );

        $competition->set_num_seasons( 5 );
        $this->assertSame( 5, $competition->num_seasons );

        $competition->set_num_entries( 32 );
        $this->assertSame( 32, $competition->num_entries );

        $competition->set_num_players( 64 );
        $this->assertSame( 64, $competition->num_players );

        $competition->set_num_rubbers( 4 );
        $this->assertSame( 4, $competition->num_rubbers );

        $competition->set_num_sets( 3 );
        $this->assertSame( 3, $competition->num_sets );

        $competition->set_round_length( 7 );
        $this->assertSame( 7, $competition->round_length );

        $competition->set_filler_weeks( 2 );
        $this->assertSame( 2, $competition->filler_weeks );

        $competition->set_teams_per_club( 3 );
        $this->assertSame( 3, $competition->teams_per_club );

        $competition->set_teams_prom_relg( 2 );
        $this->assertSame( 2, $competition->teams_prom_relg );

        $competition->set_max_teams( 12 );
        $this->assertSame( 12, $competition->max_teams );

        $competition->set_lowest_promotion( 4 );
        $this->assertSame( 4, $competition->lowest_promotion );

        $competition->set_offset( 10 );
        $this->assertSame( 10, $competition->offset );

        $competition->set_home_away_diff( 1 );
        $this->assertSame( 1, $competition->home_away_diff );
    }

    public function test_additional_state_setters(): void {
        $competition = $this->create_competition();

        $competition->set_competition_code( 'LEAG2026' );
        $this->assertSame( 'LEAG2026', $competition->competition_code );

        $competition->set_competition_type( 'league' );
        $this->assertSame( 'league', $competition->competition_type );

        $config = new stdClass();
        $config->key = 'val';
        $competition->set_config( $config );
        $this->assertSame( $config, $competition->config );

        $competition->set_date_open( '2026-01-01' );
        $this->assertSame( '2026-01-01', $competition->date_open );

        $competition->set_date_start( '2026-03-01' );
        $this->assertSame( '2026-03-01', $competition->date_start );

        $competition->set_date_end( '2026-06-01' );
        $this->assertSame( '2026-06-01', $competition->date_end );

        $competition->set_entries( 18 );
        $this->assertSame( 18, $competition->entries );

        $competition->set_event_index( [ '10' => 0 ] );
        $this->assertSame( [ '10' => 0 ], $competition->event_index );

        $competition->set_fixed_match_dates( true );
        $this->assertTrue( $competition->fixed_match_dates );

        $competition->set_grade( 'County' );
        $this->assertSame( 'County', $competition->grade );

        $competition->set_home_away( true );
        $this->assertTrue( $competition->home_away );

        $competition->set_match_day_restriction( true );
        $this->assertTrue( $competition->match_day_restriction );

        $competition->set_match_day_weekends( true );
        $this->assertTrue( $competition->match_day_weekends );

        $competition->set_match_days_allowed( [ 'Mon', 'Wed' ] );
        $this->assertSame( [ 'Mon', 'Wed' ], $competition->match_days_allowed );

        $competition->set_primary_league( 105 );
        $this->assertSame( 105, $competition->primary_league );

        $competition->set_reverse_rubbers( true );
        $this->assertTrue( $competition->reverse_rubbers );

        $competition->set_rules( [ 'rule1' => 'desc' ] );
        $this->assertSame( [ 'rule1' => 'desc' ], $competition->rules );

        $competition->set_scoring( 'standard' );
        $this->assertSame( 'standard', $competition->scoring );

        $competition->set_season( 2026 );
        $this->assertSame( 2026, $competition->season );

        $competition->set_start_time( [ 'hour' => 19, 'minutes' => 30 ] );
        $this->assertSame( [ 'hour' => 19, 'minutes' => 30 ], $competition->start_time );

        $competition->set_venue( 'Clubhouse' );
        $this->assertSame( 'Clubhouse', $competition->venue );

        $competition->set_winners( [ 'Club A' ] );
        $this->assertSame( [ 'Club A' ], $competition->winners );

        // Test set_num_courts_available with club ID and batch replace.
        $competition->set_num_courts_available( 101, 4 );
        $this->assertSame( [ 101 => 4 ], $competition->num_courts_available );

        $competition->replace_num_courts_available( [ 101 => 4, 102 => 2 ] );
        $this->assertSame( [ 101 => 4, 102 => 2 ], $competition->num_courts_available );
    }

    public function test_season_lifecycle_unknown_season_throws_exception(): void {
        $seasons = [
            '2026' => [
                'name' => '2026',
            ],
        ];

        $competition = $this->create_competition( $seasons );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Cannot transition unknown season "9999".' );

        $competition->open_season( '9999' );
    }

    public function test_season_lifecycle_empty_seasons_throws_exception(): void {
        $competition = $this->create_competition( [] );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Cannot transition season: competition has no seasons configured.' );

        $competition->open_season();
    }

    public function test_season_lifecycle_forced_phase_semantics(): void {
        // Historical season whose calendar date ended long ago.
        $seasons = [
            '2020' => [
                'name'     => '2020',
                'date_end' => '2020-01-01',
            ],
        ];

        $competition = $this->create_competition( $seasons );
        // Date-based derivation marks this past season as 'end'.
        $this->assertSame( 'end', $competition->current_phase );
        $this->assertTrue( $competition->is_complete );

        // Explicit lifecycle transition forces the commanded phase despite calendar dates.
        $competition->open_season( '2020' );

        $this->assertSame( 'open', $competition->current_phase );
        $this->assertTrue( $competition->is_open );
        $this->assertFalse( $competition->is_complete );
    }

    public function test_season_lifecycle_with_partial_date_data(): void {
        $seasons = [
            '2026' => [
                'name'        => '2026',
                'match_dates' => [ '2026-05-01', '2026-05-15' ],
            ],
        ];

        $competition = $this->create_competition( $seasons );
        $competition->open_season( '2026' );

        $this->assertSame( 'open', $competition->current_phase );
        $this->assertSame( '2026-05-01', $competition->current_season['date_start'] );
        $this->assertSame( '2026-05-15', $competition->current_season['date_end'] );

        $competition->close_season( '2026' );
        $this->assertSame( 'close', $competition->current_phase );

        $competition->start_season( '2026' );
        $this->assertSame( 'start', $competition->current_phase );

        $competition->complete_season( '2026' );
        $this->assertSame( 'complete', $competition->current_phase );
    }

    public function test_type_transition_synchronizes_finals_and_preserves_overrides(): void {
        $competition = $this->create_competition();

        // 1. Initial league type has empty finals.
        $this->assertTrue( $competition->is_league );
        $this->assertEmpty( $competition->finals );

        // 2. Transitioning to cup generates cup finals via Competition_Policy.
        $competition->set_type( Competition_Type::CUP );
        $this->assertTrue( $competition->is_cup );
        $this->assertNotEmpty( $competition->finals );
        $this->assertCount( 4, $competition->finals );

        // 3. Transitioning back to league clears generated cup finals avoiding stale state.
        $competition->set_type( Competition_Type::LEAGUE );
        $this->assertTrue( $competition->is_league );
        $this->assertEmpty( $competition->finals );

        // 4. Setting explicit custom finals marks them as an external override.
        $custom_finals = [ 'final' => [ 'name' => 'Custom Final Match' ] ];
        $competition->set_finals( $custom_finals );
        $this->assertSame( $custom_finals, $competition->finals );

        // 5. Subsequent type transitions preserve the explicit override.
        $competition->set_type( Competition_Type::CUP );
        $this->assertSame( $custom_finals, $competition->finals );

        $competition->set_type( Competition_Type::LEAGUE );
        $this->assertSame( $custom_finals, $competition->finals );
    }

    public function test_seasons_mutation_synchronizes_num_seasons(): void {
        $competition = $this->create_competition( [ '2025' => [ 'name' => '2025' ] ] );
        $this->assertSame( 1, $competition->num_seasons );

        // Updating seasons synchronizes count.
        $competition->update_seasons(
            [
                '2025' => [ 'name' => '2025' ],
                '2026' => [ 'name' => '2026' ],
            ]
        );
        $this->assertSame( 2, $competition->num_seasons );

        // Adding a season synchronizes count.
        $new_season       = new stdClass();
        $new_season->name = '2027';
        $competition->add_season( $new_season );
        $this->assertSame( 3, $competition->num_seasons );
    }

    public function test_getters_return_consistent_values(): void {
        $competition = $this->create_competition();

        $this->assertSame( 1, $competition->get_id() );
        $this->assertSame( 'Premier League', $competition->get_name() );
        $this->assertSame( 'league', $competition->get_type() );
        $this->assertSame( Competition_Type::LEAGUE, $competition->get_competition_type() );
        $this->assertSame( 'Adults', $competition->get_age_group() );
        $this->assertIsArray( $competition->get_settings() );
        $this->assertIsArray( $competition->get_seasons() );
    }
}
