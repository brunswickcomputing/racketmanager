<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use Error;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Event_Test_Factory;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Unit tests for Event aggregate encapsulation, invariants, lifecycle, and domain mutation methods.
 */
final class Event_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Helper to create a Competition instance for testing.
     *
     * @param Competition_Type|string $type Competition type string or enum.
     * @param array                   $settings Optional settings.
     * @return Competition
     */
    private function create_competition( Competition_Type|string $type = Competition_Type::LEAGUE, array $settings = array() ): Competition {
        return Competition_Test_Factory::create(
            10,
            'Main Competition',
            $type,
            null,
            $settings
        );
    }

    /**
     * Helper to build a standard Event aggregate.
     *
     * @param array $settings Optional event settings.
     * @param array $seasons Optional seasons array.
     * @param Competition|null $competition Optional parent competition.
     * @return Event
     */
    private function create_event(
        array $settings = array(),
        array $seasons = array(),
        ?Competition $competition = null
    ): Event {
        $parent_comp = $competition ?? $this->create_competition();
        return Event_Factory::create_from_dto(
            new Event_Hydration_DTO(
                id: 20,
                name: 'Men\'s Doubles',
                type: 'MD',
                num_sets: 3,
                num_rubbers: 4,
                competition_id: $parent_comp->id,
                settings: $settings,
                seasons: $seasons,
                status: 'active'
            ),
            $parent_comp
        );
    }

    // =========================================================================
    // 1. Encapsulation & Asymmetric Visibility (public private(set))
    // =========================================================================

    public function test_public_properties_are_readable(): void {
        $competition = $this->create_competition();
        $event       = $this->create_event(
            settings: array( 'mode' => 'league' ),
            competition: $competition
        );

        $this->assertSame( 20, $event->id );
        $this->assertSame( 'Men\'s Doubles', $event->name );
        $this->assertSame( 'MD', $event->type );
        $this->assertSame( 'active', $event->status );
        $this->assertSame( 3, $event->num_sets );
        $this->assertSame( 4, $event->num_rubbers );
        $this->assertSame( 10, $event->competition_id );
        $this->assertSame( $competition, $event->competition );
        $this->assertSame( 'tennis', $event->sport );
        $this->assertSame( 'tennis', $event->point_rule );
        $this->assertSame( '%d-%d', $event->point_format );
        $this->assertSame( '%d-%d', $event->point_2_format );
        $this->assertSame( 'league', $event->mode );
        $this->assertIsArray( $event->default_match_start_time );
        $this->assertSame( 19, $event->default_match_start_time['hour'] );
        $this->assertSame( 30, $event->default_match_start_time['minutes'] );
        $this->assertIsArray( $event->standings );
        $this->assertSame( 0, $event->num_ascend );
        $this->assertSame( 0, $event->num_descend );
        $this->assertSame( 0, $event->num_relegation );
        $this->assertSame( 10, $event->num_matches_per_page );
        $this->assertFalse( $event->is_championship );
        $this->assertFalse( $event->is_box );
        $this->assertNull( $event->parent_league );
        $this->assertNull( $event->draw );
        $this->assertSame( array(), $event->matches );
        $this->assertSame( array(), $event->stages );
        $this->assertSame( array(), $event->rounds );
        $this->assertNull( $event->champion );
        $this->assertNull( $event->runner_up );
        $this->assertSame( 0, $event->primary_league );
        $this->assertNull( $event->primary_league_obj );
    }

    public function test_external_write_to_scalar_property_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        // Direct external assignment must fail at PHP engine level.
        $event->name = 'New Name';
    }

    public function test_external_write_to_id_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $event->id = 999;
    }

    public function test_external_write_to_status_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $event->status = 'archived';
    }

    public function test_external_write_to_competition_object_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $event->competition = null;
    }

    public function test_external_write_to_parent_league_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $event->parent_league = null;
    }

    public function test_external_write_to_array_property_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $event->matches = array( 1, 2, 3 );
    }

    public function test_direct_external_array_element_modification_throws_error(): void {
        $event = $this->create_event();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot (indirectly )?modify private\(set\) property/' );

        // In PHP 8.4, modifying an array element on a private(set) property externally throws Error.
        $event->default_match_start_time['hour'] = 18;
    }

    public function test_subclass_cannot_modify_private_set_property(): void {
        $subclass = new class() extends Event {
            public function mutate_from_subclass(): void {
                $this->name = 'Subclass Mutated';
            }
        };

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        // PHP 8.4 private(set) restricts mutation strictly to the declaring class Event.
        $subclass->mutate_from_subclass();
    }

    public function test_meta_storage_is_strictly_encapsulated(): void {
        $event = $this->create_event();

        $this->assertNull( $event->get_meta( 'custom_key' ) );
        $this->assertSame( 'default_value', $event->get_meta( 'custom_key', 'default_value' ) );

        $event->set_meta( 'custom_key', array( 'foo' => 'bar' ) );
        $this->assertSame( array( 'foo' => 'bar' ), $event->get_meta( 'custom_key' ) );
    }

    // =========================================================================
    // 2. Domain Setters & State Invariants
    // =========================================================================

    public function test_domain_setters_mutate_internal_state_correctly(): void {
        $event = $this->create_event();

        $event->set_name( 'Championship Singles' );
        $this->assertSame( 'Championship Singles', $event->name );
        $this->assertSame( 'Championship Singles', $event->get_name() );

        $event->set_id( 42 );
        $this->assertSame( 42, $event->id );
        $this->assertSame( 42, $event->get_id() );

        $event->set_status( 'completed' );
        $this->assertSame( 'completed', $event->status );

        $event->set_sport( 'badminton' );
        $this->assertSame( 'badminton', $event->sport );

        $event->set_point_rule( 'two_points' );
        $this->assertSame( 'two_points', $event->point_rule );

        $event->set_point_format( '%d:%d' );
        $this->assertSame( '%d:%d', $event->point_format );

        $event->set_point_2_format( '%d/%d' );
        $this->assertSame( '%d/%d', $event->point_2_format );

        $event->set_num_sets( 5 );
        $this->assertSame( 5, $event->num_sets );
        $this->assertSame( 5, $event->get_num_sets() );

        $event->set_num_rubbers( 6 );
        $this->assertSame( 6, $event->num_rubbers );
        $this->assertSame( 6, $event->get_num_rubbers() );

        $event->set_type( 'WS' );
        $this->assertSame( 'WS', $event->type );
        $this->assertSame( 'WS', $event->get_type() );

        $event->set_scoring( 'best_of_3' );
        $this->assertSame( 'best_of_3', $event->scoring );

        $event->set_offset( 14 );
        $this->assertSame( 14, $event->offset );

        $event->set_age_limit( 'U18' );
        $this->assertSame( 'U18', $event->age_limit );

        $event->set_age_offset( '+1' );
        $this->assertSame( '+1', $event->age_offset );

        $event->set_reverse_rubbers( true );
        $this->assertTrue( $event->reverse_rubbers );

        $event->set_reverse_rubbers( 1 );
        $this->assertSame( 1, $event->reverse_rubbers );

        $event->set_reverse_rubbers( 0 );
        $this->assertSame( 0, $event->reverse_rubbers );

        $event->set_is_championship( true );
        $this->assertTrue( $event->is_championship );

        $event->set_is_box( true );
        $this->assertTrue( $event->is_box );

        $event->set_entry_open( true );
        $this->assertTrue( $event->entry_open );

        $event->set_num_match_days( 8 );
        $this->assertSame( 8, $event->num_match_days );

        $event->set_num_seasons( 3 );
        $this->assertSame( 3, $event->num_seasons );

        $event->set_league_count( 4 );
        $this->assertSame( 4, $event->num_leagues );
    }

    public function test_competition_mutation_synchronizes_competition_id(): void {
        $event       = $this->create_event();
        $competition = Competition_Test_Factory::create(
            55,
            'Winter Trophy',
            Competition_Type::CUP
        );

        $event->set_competition( $competition );
        $this->assertSame( $competition, $event->competition );
        $this->assertSame( 55, $event->competition_id );
        $this->assertSame( 55, $event->get_competition_id() );

        $event->set_competition( null );
        $this->assertNull( $event->competition );
        // Unsetting competition object does not corrupt stored scalar competition_id.
        $this->assertSame( 55, $event->competition_id );
    }

    public function test_parent_league_and_primary_league_mutators(): void {
        $event       = $this->create_event();
        $test_league = League_Test_Factory::create( id: 1, title: 'Division 1' );

        $event->set_parent_league( $test_league );
        $this->assertSame( $test_league, $event->parent_league );

        $event->set_primary_league( 99 );
        $this->assertSame( 99, $event->primary_league );

        $event->set_primary_league_obj( $test_league );
        $this->assertSame( $test_league, $event->primary_league_obj );
    }

    public function test_settings_mutation_and_selective_application(): void {
        $event = $this->create_event();

        $event->set_settings( array( 'custom' => 'val' ) );
        $this->assertSame( array( 'custom' => 'val' ), $event->settings );
        $this->assertSame( array( 'custom' => 'val' ), $event->get_settings() );

        // Apply settings selectively onto aggregate properties.
        $event->apply_settings(
            array(
                'num_seeds'    => 8,
                'team_ranking' => 'manual',
                'standings'    => array( 'points' => 3 ),
                'illegal_key'  => 'injected',
            )
        );

        $this->assertSame( 8, $event->num_seeds );
        $this->assertSame( 'manual', $event->team_ranking );
        $this->assertSame( 3, $event->standings['points'] );
        $this->assertFalse( property_exists( $event, 'illegal_key' ) );
    }

    // =========================================================================
    // 3. Tournament / Knockout State (Matches, Stages, Rounds, Winners)
    // =========================================================================

    public function test_knockout_structure_mutations(): void {
        $event = $this->create_event();

        $matches = array(
            (object) array( 'id' => 101, 'round' => 'quarter' ),
            (object) array( 'id' => 102, 'round' => 'final' ),
        );
        $stages = array( 'Qualifying', 'Main Draw' );
        $rounds = array( 'Quarter-Final', 'Semi-Final', 'Final' );

        $event->set_matches( $matches );
        $event->set_stages( $stages );
        $event->set_rounds( $rounds );
        $event->set_draw( 'single_elimination_16' );
        $event->set_groups( '4' );
        $event->set_teams_per_group( 4 );
        $event->set_num_advance( 2 );

        $this->assertSame( $matches, $event->matches );
        $this->assertSame( $stages, $event->stages );
        $this->assertSame( $rounds, $event->rounds );
        $this->assertSame( 'single_elimination_16', $event->draw );
        $this->assertSame( '4', $event->groups );
        $this->assertSame( 4, $event->teams_per_group );
        $this->assertSame( 2, $event->num_advance );
    }

    public function test_tournament_winner_and_runner_up_mutations(): void {
        $event = $this->create_event();

        $event->set_champion( 1001 );
        $event->set_runner_up( 1002 );
        $this->assertSame( 1001, $event->champion );
        $this->assertSame( 1002, $event->runner_up );

        // record_winner atomically sets both champion and runner-up.
        $event->record_winner( 2001, 2002 );
        $this->assertSame( 2001, $event->champion );
        $this->assertSame( 2002, $event->runner_up );

        // Nullable runner-up support when only champion is determined.
        $event->record_winner( 3001, null );
        $this->assertSame( 3001, $event->champion );
        $this->assertNull( $event->runner_up );
    }

    // =========================================================================
    // 4. Configuration Updates & Invariants (update_configuration / set_config)
    // =========================================================================

    public function test_update_configuration_detects_changes_and_updates_state(): void {
        $competition = Competition_Test_Factory::create(
            10,
            'League Competition',
            Competition_Type::LEAGUE,
            null,
            array( 'mode' => 'league' )
        );
        $event = $this->create_event( competition: $competition );

        $config = (object) array(
            'name'       => 'Updated Event Name',
            'type'       => 'MS',
            'num_sets'   => 5,
            'scoring'    => 'best_of_5',
            'offset'     => 7,
            'age_limit'  => 'O35',
            'age_offset' => '-1',
        );

        $changed = $event->update_configuration( $config );

        $this->assertTrue( $changed );
        $this->assertSame( 'Updated Event Name', $event->name );
        $this->assertSame( 'MS', $event->type );
        $this->assertSame( 5, $event->num_sets );
        $this->assertSame( 'best_of_5', $event->scoring );
        $this->assertSame( 7, $event->offset );
        $this->assertSame( 'O35', $event->age_limit );
        $this->assertSame( '-1', $event->age_offset );
        $this->assertIsArray( $event->settings );
        $this->assertSame( 'best_of_5', $event->settings['scoring'] );
    }

    public function test_update_configuration_with_identical_data_returns_false(): void {
        $competition = Competition_Test_Factory::create(
            10,
            'League Competition',
            Competition_Type::LEAGUE,
            null,
            array( 'mode' => 'league' )
        );
        $event = $this->create_event( competition: $competition );

        $config = (object) array(
            'name'       => $event->name,
            'type'       => $event->type,
            'num_sets'   => $event->num_sets,
            'scoring'    => $event->scoring,
            'offset'     => $event->offset,
            'age_limit'  => $event->age_limit,
            'age_offset' => $event->age_offset,
        );

        // First apply the configuration to establish initial state.
        $event->update_configuration( $config );

        // Second call with identical configuration must detect no changes.
        $changed = $event->update_configuration( $config );
        $this->assertFalse( $changed, 'Identical configuration must return false' );
    }

    public function test_update_configuration_for_tournament_ignores_offset_update(): void {
        $tournament = Competition_Test_Factory::create(
            20,
            'Tournament Competition',
            Competition_Type::TOURNAMENT
        );
        $event = $this->create_event( competition: $tournament );
        $event->set_offset( 10 );

        $config = (object) array(
            'name'       => $event->name,
            'type'       => $event->type,
            'num_sets'   => $event->num_sets,
            'scoring'    => $event->scoring,
            'offset'     => 99, // Should be ignored in tournament mode
            'age_limit'  => $event->age_limit,
            'age_offset' => $event->age_offset,
        );

        $event->update_configuration( $config );
        $this->assertSame( 10, $event->offset, 'Tournament event must not update offset' );
    }

    public function test_update_configuration_for_team_entry_updates_rubbers(): void {
        $team_comp = Competition_Test_Factory::create(
            30,
            'Team Competition',
            Competition_Type::LEAGUE,
            null,
            array( 'mode' => 'league', 'is_team_entry' => true )
        );
        $event = $this->create_event( competition: $team_comp );

        $config = (object) array(
            'name'            => $event->name,
            'type'            => $event->type,
            'num_sets'        => $event->num_sets,
            'scoring'         => $event->scoring,
            'offset'          => $event->offset,
            'age_limit'       => $event->age_limit,
            'age_offset'      => $event->age_offset,
            'num_rubbers'     => 6,
            'reverse_rubbers' => '1',
        );

        $changed = $event->update_configuration( $config );
        $this->assertTrue( $changed );
        $this->assertSame( 6, $event->num_rubbers );
        $this->assertSame( '1', $event->reverse_rubbers );
    }

    public function test_update_configuration_handles_integer_reverse_rubbers_match_days_allowed_and_partial_config(): void {
        $league_comp = Competition_Test_Factory::create(
            40,
            'League Team Competition',
            Competition_Type::LEAGUE,
            null,
            array(
                'mode'          => 'league',
                'is_team_entry' => true,
            )
        );
        $event = $this->create_event( competition: $league_comp );

        // Partial configuration array with integer reverse_rubbers and match_days_allowed.
        $partial_config = array(
            'reverse_rubbers'    => 1,
            'match_days_allowed' => array( 'mon' => 1, 'wed' => 1 ),
        );

        $changed = $event->update_configuration( $partial_config );
        $this->assertTrue( $changed );
        $this->assertSame( 1, $event->reverse_rubbers );
        $this->assertIsArray( $event->match_days_allowed );
        $this->assertSame( 1, $event->match_days_allowed['mon'] );
        $this->assertSame( 0, $event->match_days_allowed['tue'] ?? 0 );
        $this->assertSame( 1, $event->match_days_allowed['wed'] );
    }

    public function test_set_config_delegates_to_update_configuration(): void {
        $event  = $this->create_event();
        $config = (object) array(
            'name'       => 'Renamed Event',
            'type'       => 'XD',
            'num_sets'   => 3,
            'scoring'    => null,
            'offset'     => 0,
            'age_limit'  => null,
            'age_offset' => null,
        );

        $changed = $event->set_config( $config );
        $this->assertTrue( $changed );
        $this->assertSame( 'Renamed Event', $event->name );
        $this->assertSame( 'XD', $event->type );
    }

    // =========================================================================
    // 5. Season Lifecycle & Transitions
    // =========================================================================

    public function test_set_season_with_existing_season_sets_current_season_and_match_days(): void {
        $seasons = array(
            '2025' => array(
                'name'           => '2025',
                'num_match_days' => 10,
            ),
            '2026' => array(
                'name'           => '2026',
                'num_match_days' => 14,
            ),
        );

        $event = $this->create_event( seasons: $seasons );
        $event->set_season( '2025' );

        $this->assertSame( '2025', $event->get_season() );
        $this->assertSame( 10, $event->num_match_days );
        $this->assertSame( '2025', $event->current_season['name'] );

        // Transition to 2026 season.
        $event->set_season( '2026' );
        $this->assertSame( '2026', $event->get_season() );
        $this->assertSame( 14, $event->num_match_days );
    }

    public function test_set_season_calculates_match_days_from_dates_array(): void {
        $seasons = array(
            '2026' => array(
                'name'        => '2026',
                'match_dates' => array( '2026-05-01', '2026-05-08', '2026-05-15' ),
            ),
        );

        $event = $this->create_event( seasons: $seasons );
        $event->set_season( '2026' );

        $this->assertSame( 3, $event->num_match_days );
    }

    public function test_set_season_with_null_selects_latest_season(): void {
        $seasons = array(
            '2024' => array( 'name' => '2024', 'num_match_days' => 8 ),
            '2025' => array( 'name' => '2025', 'num_match_days' => 12 ),
        );

        $event = $this->create_event( seasons: $seasons );
        $event->set_season( null );

        $this->assertSame( '2025', $event->get_season() );
        $this->assertSame( 12, $event->num_match_days );
    }

    public function test_set_season_strict_mode_with_unknown_season_sets_false(): void {
        $seasons = array(
            '2026' => array( 'name' => '2026', 'num_match_days' => 10 ),
        );

        $event = $this->create_event( seasons: $seasons );
        $event->set_season( 'non_existent_season', false, true );

        $this->assertFalse( $event->current_season );
        $this->assertNull( $event->get_season() );
    }

    public function test_get_season_event_lookups(): void {
        $seasons = array(
            '2025' => array( 'name' => '2025', 'date_start' => '2025-05-01' ),
            '2026' => array( 'name' => '2026', 'date_start' => '2026-05-01' ),
        );

        $event = $this->create_event( seasons: $seasons );

        $season_data = $event->get_season_event( '2025' );
        $this->assertIsArray( $season_data );
        $this->assertSame( '2025', $season_data['name'] );

        // When season is false, falls back to latest.
        $latest = $event->get_season_event( false );
        $this->assertIsArray( $latest );
        $this->assertSame( '2026', $latest['name'] );

        // Empty seasons returns false.
        $empty_event = $this->create_event( seasons: array() );
        $this->assertFalse( $empty_event->get_season_event( '2025' ) );
    }

    public function test_update_seasons_and_update_season(): void {
        $initial_seasons = array(
            '2025' => array( 'name' => '2025' ),
        );

        $event = $this->create_event( seasons: $initial_seasons );

        // Same seasons returns false.
        $this->assertFalse( $event->update_seasons( $initial_seasons ) );

        // New seasons returns true and updates JSON.
        $updated_seasons = array(
            '2025' => array( 'name' => '2025' ),
            '2026' => array( 'name' => '2026' ),
        );
        $this->assertTrue( $event->update_seasons( $updated_seasons ) );
        $this->assertSame( $updated_seasons, $event->get_seasons() );

        // update_season adds and ksorts.
        $event->update_season( array( 'name' => '2024' ) );
        $seasons = $event->get_seasons();
        $keys    = array_keys( $seasons );
        $this->assertSame( array( 2024, 2025, 2026 ), $keys );
    }

    // =========================================================================
    // 6. Factory and Test Factory Integration
    // =========================================================================

    public function test_hydrates_via_event_hydration_dto_with_all_aggregate_fields(): void {
        $test_league = League_Test_Factory::create( id: 1, title: 'Division 1' );
        $dto         = new Event_Hydration_DTO(
            id: 77,
            name: 'Grand Slam Cup',
            type: 'MS',
            num_sets: 5,
            num_rubbers: 1,
            competition_id: 10,
            settings: array( 'scoring' => 'grand_slam' ),
            seasons: array( '2026' => array( 'name' => '2026' ) ),
            matches: array( (object) array( 'id' => 500 ) ),
            stages: array( 'Round of 16', 'Quarter-Final', 'Semi-Final', 'Final' ),
            rounds: array( 'Final' ),
            champion: 800,
            runner_up: 801,
            parent_league: $test_league,
            draw: 'draw_gs_128',
            status: 'completed',
            primary_league: 55
        );

        $event = Event_Factory::create_from_dto( $dto );

        $this->assertSame( 77, $event->id );
        $this->assertSame( 'Grand Slam Cup', $event->name );
        $this->assertSame( 'MS', $event->type );
        $this->assertSame( 5, $event->num_sets );
        $this->assertSame( 1, $event->num_rubbers );
        $this->assertSame( 10, $event->competition_id );
        $this->assertSame( 800, $event->champion );
        $this->assertSame( 801, $event->runner_up );
        $this->assertSame( $test_league, $event->parent_league );
        $this->assertSame( 'draw_gs_128', $event->draw );
        $this->assertSame( 'completed', $event->status );
        $this->assertSame( 55, $event->primary_league );
        $this->assertCount( 1, $event->matches );
        $this->assertCount( 4, $event->stages );
    }

    public function test_event_test_factory_presets_produce_valid_aggregates(): void {
        $league_event = Event_Test_Factory::league_event();
        $this->assertInstanceOf( Event::class, $league_event );
        $this->assertSame( 'MD', $league_event->type );
        $this->assertTrue( $league_event->competition->is_league );

        $tournament_event = Event_Test_Factory::tournament_event(
            stages: array( 'Quarter-Final', 'Semi-Final', 'Final' )
        );
        $this->assertInstanceOf( Event::class, $tournament_event );
        $this->assertSame( 'MS', $tournament_event->type );
        $this->assertTrue( $tournament_event->competition->is_tournament );
        $this->assertCount( 3, $tournament_event->stages );

        $cup_event = Event_Test_Factory::cup_event();
        $this->assertInstanceOf( Event::class, $cup_event );
        $this->assertSame( 'MD', $cup_event->type );
        $this->assertTrue( $cup_event->competition->is_cup );
    }
}
