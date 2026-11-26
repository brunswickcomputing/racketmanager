<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use stdClass;

final class League_Encapsulation_Test extends TestCase {

    /**
     * Verifies that direct mutation from outside the aggregate throws an Error
     * for all 42 private(set) properties.
     *
     * @param string $property Property name.
     * @param mixed  $value    Property value.
     */
    #[DataProvider( 'all_42_properties_provider' )]
    public function test_direct_property_write_is_blocked_for_all_42_properties( string $property, mixed $value ): void {
        $league = new League();

        $this->expectException( Error::class );
        $this->expectExceptionMessage( 'Cannot modify private(set) property ' . League::class . '::$' . $property );

        $league->{$property} = $value;
    }

    /**
     * Provider of all 42 private(set) properties on the League aggregate.
     *
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function all_42_properties_provider(): array {
        return array(
            'id'                => array( 'id', 1 ),
            'name'              => array( 'name', 'Premier Division' ),
            'title'             => array( 'title', 'Premier Division' ),
            'seasons'           => array( 'seasons', array( '2026' => array() ) ),
            'sequence'          => array( 'sequence', '1' ),
            'num_seasons'       => array( 'num_seasons', 2 ),
            'sport'             => array( 'sport', 'tennis' ),
            'point_rule'        => array( 'point_rule', 'three' ),
            'point_format'      => array( 'point_format', '%d-%d' ),
            'point_2_format'    => array( 'point_2_format', '%d-%d' ),
            'mode'              => array( 'mode', 'default' ),
            'standings'         => array( 'standings', array( 'team_1' => array() ) ),
            'groups'            => array( 'groups', 'A,B' ),
            'num_advance'       => array( 'num_advance', 2 ),
            'teams'             => array( 'teams', array() ),
            'num_teams'         => array( 'num_teams', 4 ),
            'num_teams_total'   => array( 'num_teams_total', 8 ),
            'matches'           => array( 'matches', array() ),
            'num_matches_total' => array( 'num_matches_total', 10 ),
            'num_matches'       => array( 'num_matches', 5 ),
            'current_season'    => array( 'current_season', array( 'name' => '2026' ) ),
            'num_match_days'    => array( 'num_match_days', 4 ),
            'match_day'         => array( 'match_day', 1 ),
            'settings'          => array( 'settings', array( 'key' => 'val' ) ),
            'team_index'        => array( 'team_index', array( 1 => 0 ) ),
            'is_championship'   => array( 'is_championship', true ),
            'championship'      => array( 'championship', null ),
            'event_id'          => array( 'event_id', 10 ),
            'num_sets'          => array( 'num_sets', 3 ),
            'num_rubbers'       => array( 'num_rubbers', 4 ),
            'num_sets_to_win'   => array( 'num_sets_to_win', 2 ),
            'competition_type'  => array( 'competition_type', 'league' ),
            'type'              => array( 'type', 'singles' ),
            'event'             => array( 'event', null ),
            'scoring'           => array( 'scoring', 'normal' ),
            'is_final'          => array( 'is_final', true ),
            'entry_type'        => array( 'entry_type', 'team' ),
            'season'            => array( 'season', '2026' ),
            'players'           => array( 'players', array() ),
            'team'              => array( 'team', null ),
            'player'            => array( 'player', null ),
            'finals'            => array( 'finals', array() ),
        );
    }

    /**
     * Verifies that all typed intention-revealing domain mutators update internal state correctly
     * and maintain read backward compatibility across the aggregate boundary.
     */
    public function test_typed_mutators_update_internal_state_and_preserve_read_api(): void {
        $league = new League();

        $league->set_id( 101 );
        $this->assertSame( 101, $league->id );

        $league->set_name( 'Division 1' );
        $this->assertSame( 'Division 1', $league->name );

        $league->set_sequence( '2' );
        $this->assertSame( '2', $league->sequence );

        $seasons = array( '2026' => array( 'name' => '2026' ) );
        $league->set_seasons( $seasons );
        $this->assertSame( $seasons, $league->seasons );

        $league->set_num_seasons( 3 );
        $this->assertSame( 3, $league->num_seasons );

        $league->set_sport( 'badminton' );
        $this->assertSame( 'badminton', $league->sport );

        $league->set_point_rule( 'two' );
        $this->assertSame( 'two', $league->point_rule );

        $league->set_point_format( '%d:%d' );
        $this->assertSame( '%d:%d', $league->point_format );

        $league->set_point_2_format( '%d/%d' );
        $this->assertSame( '%d/%d', $league->point_2_format );

        $league->set_mode( 'championship' );
        $this->assertSame( 'championship', $league->mode );

        $standings = array( 'team_1' => array( 'pts' => 10 ) );
        $league->set_standings( $standings );
        $this->assertSame( $standings, $league->standings );

        $league->set_groups( 'Group A, Group B' );
        $this->assertSame( 'Group A, Group B', $league->groups );

        $league->set_num_advance( 2 );
        $this->assertSame( 2, $league->num_advance );

        $team_mock = (object) array( 'id' => 1, 'name' => 'Team 1' );
        $league->set_teams( array( $team_mock ) );
        $this->assertSame( array( $team_mock ), $league->teams );

        $league->set_num_teams_count( 10 );
        $this->assertSame( 10, $league->num_teams );

        $league->set_num_teams_total( 16 );
        $this->assertSame( 16, $league->num_teams_total );

        $league->set_num_teams( false );
        $this->assertSame( 16, $league->num_teams );

        $league->set_num_teams( 8 );
        $this->assertSame( 8, $league->num_teams );

        $match_mock = (object) array( 'id' => 201 );
        $league->set_matches( array( $match_mock ) );
        $this->assertSame( array( $match_mock ), $league->matches );

        $league->set_num_matches_total( 24 );
        $this->assertSame( 24, $league->num_matches_total );

        $league->set_num_matches( 12 );
        $this->assertSame( 12, $league->num_matches );

        $season_info = array( 'name' => '2026' );
        $league->set_current_season( $season_info );
        $this->assertSame( $season_info, $league->current_season );

        $league->set_num_match_days( 5 );
        $this->assertSame( 5, $league->num_match_days );

        $league->set_match_day( 3 );
        $this->assertSame( 3, $league->match_day );

        $settings = array( 'opt' => 'val' );
        $league->set_settings( $settings );
        $this->assertSame( $settings, $league->settings );

        $team_index = array( 10 => 0 );
        $league->set_team_index( $team_index );
        $this->assertSame( $team_index, $league->team_index );

        $league->set_is_championship( true );
        $this->assertTrue( $league->is_championship );

        $championship = $this->createStub( Championship::class );
        $league->set_championship( $championship );
        $this->assertSame( $championship, $league->championship );

        $league->set_event_id( 7 );
        $this->assertSame( 7, $league->event_id );

        $event = $this->createStub( Event::class );
        $league->set_event( $event );
        $this->assertSame( $event, $league->event );

        $league->set_num_sets( 5 );
        $this->assertSame( 5, $league->num_sets );

        $league->set_num_rubbers( 4 );
        $this->assertSame( 4, $league->num_rubbers );

        $league->set_num_sets_to_win( 3 );
        $this->assertSame( 3, $league->num_sets_to_win );

        $league->set_competition_type( 'cup' );
        $this->assertSame( 'cup', $league->competition_type );
        $this->assertSame( 'cup', $league->get_competition_type() );

        $league->set_type( 'doubles' );
        $this->assertSame( 'doubles', $league->type );

        $league->set_scoring( 'advantage' );
        $this->assertSame( 'advantage', $league->scoring );

        $finals_list = array( array( 'key' => 'final', 'name' => 'Final' ) );
        $league->set_finals_list( $finals_list );
        $this->assertSame( $finals_list, $league->finals );

        $league->set_finals( true );
        $this->assertTrue( $league->is_final );

        $league->set_is_final( false );
        $this->assertFalse( $league->is_final );

        $league->set_entry_type( 'open' );
        $this->assertSame( 'open', $league->entry_type );

        $league->set_season_name( '2026' );
        $this->assertSame( '2026', $league->season );

        $player_mock = (object) array( 'id' => 99 );
        $league->set_players( array( $player_mock ) );
        $this->assertSame( array( $player_mock ), $league->players );
        $this->assertSame( array( $player_mock ), $league->get_players() );

        $league->set_team( $team_mock );
        $this->assertSame( $team_mock, $league->team );

        $league->set_player( $player_mock );
        $this->assertSame( $player_mock, $league->player );
    }

    /**
     * Verifies nullable parameters and union type edge cases across domain mutators.
     */
    public function test_nullable_and_union_typed_mutators_accept_edge_cases(): void {
        $league = new League();

        $league->set_id( 1 );
        $league->set_id( null );
        $this->assertNull( $league->id );

        $league->set_sequence( '1' );
        $league->set_sequence( null );
        $this->assertNull( $league->sequence );

        $league->set_num_advance( 2 );
        $league->set_num_advance( null );
        $this->assertNull( $league->num_advance );

        $league->set_num_rubbers( 4 );
        $league->set_num_rubbers( null );
        $this->assertNull( $league->num_rubbers );

        $league->set_scoring( 'sets' );
        $league->set_scoring( null );
        $this->assertNull( $league->scoring );

        $championship = $this->createStub( Championship::class );
        $league->set_championship( $championship );
        $league->set_championship( null );
        $this->assertNull( $league->championship );

        $event = $this->createStub( Event::class );
        $league->set_event( $event );
        $league->set_event( null );
        $this->assertNull( $league->event );

        $team = (object) array( 'id' => 1 );
        $league->set_team( $team );
        $league->set_team( null );
        $this->assertNull( $league->team );

        $player = (object) array( 'id' => 2 );
        $league->set_player( $player );
        $league->set_player( null );
        $this->assertNull( $league->player );

        $league->set_seasons( 'serialized_string' );
        $this->assertSame( 'serialized_string', $league->seasons );

        $league->set_settings( 'serialized_settings' );
        $this->assertSame( 'serialized_settings', $league->settings );

        $finals_array = array( array( 'name' => 'Final' ) );
        $league->set_finals( $finals_array );
        $this->assertSame( $finals_array, $league->finals );

        $league->set_finals( true );
        $this->assertTrue( $league->is_final );

        $league->set_finals( null );
        $this->assertNull( $league->finals );
    }

    /**
     * Verifies bidirectional property hooks between name and title without infinite recursion.
     */
    public function test_bidirectional_property_hook_synchronization(): void {
        $league = new League();

        $league->set_name( 'Premier Division' );
        $this->assertSame( 'Premier Division', $league->name );
        $this->assertSame( 'Premier Division', $league->title );
        $this->assertSame( 'Premier Division', $league->get_name() );
        $this->assertSame( 'Premier Division', $league->get_title() );

        $league->set_title( 'Championship Division' );
        $this->assertSame( 'Championship Division', $league->name );
        $this->assertSame( 'Championship Division', $league->title );
        $this->assertSame( 'Championship Division', $league->get_name() );
        $this->assertSame( 'Championship Division', $league->get_title() );
    }

    /**
     * Verifies typed DTO construction and reflection-based hydrator compatibility.
     */
    public function test_hydration_from_dto_and_reflection_hydrator(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'League Competition',
            type: Competition_Type::LEAGUE,
            settings: array(
                'sport'          => 'tennis',
                'point_rule'     => 'two',
                'point_format'   => '%d:%d',
                'point_2_format' => '%d/%d',
            )
        );

        $event_raw                 = new stdClass();
        $event_raw->id             = 10;
        $event_raw->name           = 'Event';
        $event_raw->type           = 'doubles';
        $event_raw->competition_id = 1;
        $event_raw->num_sets       = 5;
        $event_raw->num_rubbers    = 4;
        $event_raw->scoring        = 'sets';
        $event_raw->seasons        = array();
        $event                     = Event::from_object( $event_raw, $competition );
        $event->set_scoring( 'sets' );

        $dto = new League_Hydration_DTO(
            id: 42,
            name: 'Hydrated Division',
            event_id: 10,
            sequence: '1',
            settings: array(
                'entry_type'       => 'pair',
                'competition_type' => 'cup',
            ),
            seasons: array( '2026' => array( 'name' => '2026' ) )
        );

        $league = new League( $dto, $event );

        $this->assertSame( 42, $league->id );
        $this->assertSame( 'Hydrated Division', $league->name );
        $this->assertSame( 'Hydrated Division', $league->title );
        $this->assertSame( 10, $league->event_id );
        $this->assertSame( '1', $league->sequence );
        $this->assertSame( 'tennis', $league->sport );
        $this->assertSame( 'two', $league->point_rule );
        $this->assertSame( '%d:%d', $league->point_format );
        $this->assertSame( '%d/%d', $league->point_2_format );
        $this->assertSame( 5, $league->num_sets );
        $this->assertSame( 4, $league->num_rubbers );
        $this->assertSame( 3, $league->num_sets_to_win );
        $this->assertSame( 'sets', $league->scoring );
        $this->assertSame( 'pair', $league->entry_type );
        $this->assertSame( 'doubles', $league->type );
        $this->assertSame( 'league', $league->competition_type );
        $this->assertSame( $event, $league->event );

        Typed_Property_Hydrator::assign( $league, 'num_sets', 3 );
        $this->assertSame( 3, $league->num_sets );
    }

    /**
     * Verifies factory hydration from database object rows.
     */
    public function test_hydration_from_object_row_via_factory(): void {
        $event_raw                 = new stdClass();
        $event_raw->id             = 10;
        $event_raw->name           = 'Event';
        $event_raw->type           = 'singles';
        $event_raw->competition_id = 1;
        $event                     = Event::from_object( $event_raw );

        $row           = new stdClass();
        $row->id       = 55;
        $row->title    = 'Factory Division';
        $row->event_id = 10;
        $row->sport    = 'tennis';
        $row->settings = array( 'num_sets' => 3 );

        $league = League_Factory::from_object( $row, $event );

        $this->assertInstanceOf( League::class, $league );
        $this->assertSame( 55, $league->id );
        $this->assertSame( 'Factory Division', $league->title );
        $this->assertSame( 'Factory Division', $league->name );
        $this->assertSame( 'tennis', $league->sport );
        $this->assertSame( 10, $league->event_id );
    }

    /**
     * Verifies that League_Test_Factory supports arbitrary property overrides
     * and safe property mutation on both concrete entities and test double stubs.
     */
    public function test_test_factory_arbitrary_overrides_and_set_property_helper(): void {
        $overrides = array(
            'num_sets'    => 5,
            'scoring'     => 'custom',
            'num_rubbers' => 3,
            'sport'       => 'squash',
        );

        $league = League_Test_Factory::create( overrides: $overrides );

        $this->assertSame( 5, $league->num_sets );
        $this->assertSame( 'custom', $league->scoring );
        $this->assertSame( 3, $league->num_rubbers );
        $this->assertSame( 'squash', $league->sport );

        League_Test_Factory::set_property( $league, 'title', 'Updated Overrides Title' );
        $this->assertSame( 'Updated Overrides Title', $league->title );
        $this->assertSame( 'Updated Overrides Title', $league->name );

        $stub = $this->createStub( League::class );
        League_Test_Factory::set_property( $stub, 'num_rubbers', 6 );
        League_Test_Factory::set_property( $stub, 'sport', 'tennis' );
        League_Test_Factory::set_property( $stub, 'title', 'Stub League' );

        $this->assertSame( 6, $stub->num_rubbers );
        $this->assertSame( 'tennis', $stub->sport );
        $this->assertSame( 'Stub League', $stub->title );
        $this->assertSame( 'Stub League', $stub->name );
    }
}
