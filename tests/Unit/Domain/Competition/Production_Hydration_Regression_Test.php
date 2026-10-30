<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Production_Hydration_Regression_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_event_hydrates_empty_string_serialized_settings_to_declared_types(): void {
        $competition           = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type     = Competition_Type::LEAGUE;

        $event_raw = (object) array(
            'id'             => '12',
            'name'           => 'Summer League Event',
            'type'           => 'doubles',
            'competition_id' => '1',
            'seasons'        => '',
            'settings'       => serialize(
                array(
                    'num_rubbers'          => '',
                    'num_sets'             => '',
                    'num_ascend'           => '',
                    'num_descend'          => '',
                    'num_relegation'       => '',
                    'num_matches_per_page' => '',
                    'num_advance'          => '',
                    'teams_per_group'      => '',
                    'is_box'               => '',
                    'match_place3'         => '',
                    'entry_open'           => '',
                    'fields_team'          => '',
                    'num_courts_available' => '',
                )
            ),
        );

        $event = Event::from_object( $event_raw, $competition );

        $this->assertSame( 12, $event->id );
        $this->assertSame( 1, $event->competition_id );
        foreach ( array( 'num_rubbers', 'num_sets', 'num_ascend', 'num_descend', 'num_relegation', 'num_matches_per_page', 'num_advance', 'teams_per_group' ) as $int_property ) {
            $this->assertSame( 0, $event->$int_property, "Event::\$$int_property should coerce '' to int 0." );
        }
        foreach ( array( 'is_box', 'match_place3', 'entry_open' ) as $bool_property ) {
            $this->assertFalse( $event->$bool_property, "Event::\$$bool_property should coerce '' to false." );
        }
        $this->assertSame( array(), $event->fields_team );
        $this->assertSame( array(), $event->num_courts_available );
    }

    public function test_event_hydrates_numeric_string_serialized_settings_to_declared_types(): void {
        $competition           = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type     = Competition_Type::LEAGUE;

        $event = Event::from_object(
            (object) array(
                'id'             => '12',
                'name'           => 'Summer League Event',
                'type'           => 'singles',
                'competition_id' => '1',
                'settings'       => serialize(
                    array(
                        'num_ascend'  => '2',
                        'num_descend' => '3',
                        'is_box'      => '1',
                        'entry_open'  => '0',
                    )
                ),
            ),
            $competition
        );

        $this->assertSame( 2, $event->num_ascend );
        $this->assertSame( 3, $event->num_descend );
        $this->assertTrue( $event->is_box );
        $this->assertFalse( $event->entry_open );
    }

    public function test_league_hydrates_empty_string_serialized_settings_to_declared_types(): void {
        $competition           = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type     = Competition_Type::LEAGUE;
        $event                 = Event::from_object( (object) array( 'id' => 12, 'name' => 'Summer', 'type' => 'singles', 'competition_id' => 1 ), $competition );

        $league_raw = (object) array(
            'id'       => '34',
            'title'    => 'Division 1',
            'event_id' => '12',
            'seasons'  => '',
            'settings' => serialize(
                array(
                    'num_teams_per_page'       => '',
                    'num_matches_per_page'     => '',
                    'archive_tab'              => '',
                    'num_advance'              => '',
                    'is_archive'               => '',
                    'show_match_day_selection' => '',
                    'slideshow'                => '',
                    'templates'                => '',
                )
            ),
        );

        $league = League::from_object( $league_raw, $event );

        $this->assertSame( 34, $league->id );
        $this->assertSame( 'Division 1', $league->title );
        foreach ( array( 'num_teams_per_page', 'num_matches_per_page', 'archive_tab', 'num_advance' ) as $int_property ) {
            $this->assertSame( 0, $league->$int_property, "League::\$$int_property should coerce '' to int 0." );
        }
        $this->assertFalse( $league->is_archive );
        $this->assertFalse( $league->show_match_day_selection, "'' must override the true default as false." );
        $this->assertSame( array(), $league->slideshow );
        $this->assertSame( array(), $league->templates );
    }

    public function test_league_hydrates_numeric_string_serialized_settings_to_declared_types(): void {
        $competition           = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type     = Competition_Type::LEAGUE;
        $event                 = Event::from_object( (object) array( 'id' => 12, 'name' => 'Summer', 'type' => 'singles', 'competition_id' => 1 ), $competition );

        $league = League::from_object(
            (object) array(
                'id'       => '34',
                'title'    => 'Division 1',
                'settings' => serialize(
                    array(
                        'num_teams_per_page' => '20',
                        'archive_tab'        => '2',
                        'is_archive'         => '1',
                    )
                ),
            ),
            $event
        );

        $this->assertSame( 20, $league->num_teams_per_page );
        $this->assertSame( 2, $league->archive_tab );
        $this->assertTrue( $league->is_archive );
    }

    private function make_league_with_event_type( string $event_type ): League {
        $competition           = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type     = Competition_Type::LEAGUE;
        $event                 = Event::from_object(
            (object) array(
                'id'             => 12,
                'name'           => 'Summer',
                'type'           => $event_type,
                'competition_id' => 1,
            ),
            $competition
        );

        return League::from_object( (object) array( 'id' => 10, 'title' => 'Premier Division' ), $event );
    }

    private function make_player( int $id, array $wtn, ?string $contactno = null, ?string $email = null ): Player {
        return new Player(
            new Player_Hydration_DTO(
                id: $id,
                email: $email,
                display_name: 'Player ' . $id,
                contactno: $contactno,
                wtn: $wtn
            )
        );
    }

    public function test_league_query_service_aggregates_team_ratings_and_normalises_team_details(): void {
        $league = $this->make_league_with_event_type( 'MD' );
        $this->assertSame( 'MD', $league->event->type );

        $team_rows = array(
            (object) array( 'table_id' => 101 ),
            (object) array( 'table_id' => 102 ),
            (object) array( 'table_id' => 103 ),
            (object) array( 'table_id' => 104 ),
        );

        $teams_by_table_id = array(
            // numeric-string, float and non-numeric ratings: only the numeric ones are summed.
            101 => League_Team::from_object(
                (object) array(
                    'id'      => 201,
                    'table_id' => 101,
                    'title'   => 'Team A',
                    'players' => array(
                        1 => $this->make_player( 1, array( 'D' => '10.5' ) ),
                        2 => $this->make_player( 2, array( 'D' => 4.25 ) ),
                        3 => $this->make_player( 3, array( 'D' => 'n/a' ) ),
                    ),
                )
            ),
            // no players: rating is left untouched.
            102 => League_Team::from_object( (object) array( 'id' => 202, 'table_id' => 102, 'title' => 'Team B' ) ),
            // players without a rating for the discipline: rating is 0.0.
            103 => League_Team::from_object(
                (object) array(
                    'id'      => 203,
                    'table_id' => 103,
                    'title'   => 'Team C',
                    'players' => array( 1 => $this->make_player( 4, array( 'S' => '7.0' ) ) ),
                )
            ),
            104 => League_Team::from_object( (object) array( 'id' => 204, 'table_id' => 104, 'title' => 'Team D' ) ),
        );

        $details = array(
            // captain resolved: contact details come from the captain.
            201 => (object) array( 'title' => 'Team A', 'captain' => '55', 'league_status' => 'W' ),
            // no captain / empty strings: normalised to null.
            202 => (object) array( 'title' => 'Team B', 'captain' => null, 'league_status' => '' ),
            // keys missing entirely.
            203 => (object) array( 'title' => 'Team C' ),
            // numeric-string captain that cannot be found.
            204 => (object) array( 'title' => 'Team D', 'captain' => '999', 'contactno' => '', 'contactemail' => '' ),
        );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )
            ->method( 'query_league_teams' )
            ->with( 10, $this->anything(), false )
            ->willReturn( $team_rows );
        $league_repo->method( 'get_team_details' )
            ->willReturnCallback( static fn( int $league_id, int $team_id ) => $details[ $team_id ] ?? null );

        $league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
        $league_team_repo->method( 'find_by_id' )
            ->willReturnCallback( static fn( int $id ) => $teams_by_table_id[ $id ] ?? null );

        $captain     = $this->make_player( 55, array(), '07700 900123', 'captain@example.com' );
        $player_repo = $this->createStub( Player_Repository_Interface::class );
        $player_repo->method( 'find' )
            ->willReturnCallback( static fn( int|string $id ) => 55 === (int) $id ? $captain : null );

        $service = new League_Query_Service(
            league_repository: $league_repo,
            league_team_repository: $league_team_repo,
            player_repository: $player_repo
        );

        $teams = $service->get_league_teams( $league, array( 'get_details' => true ) );

        $this->assertCount( 4, $teams );
        $by_id = array();
        foreach ( $teams as $team ) {
            $by_id[ $team->id ] = $team;
        }

        $this->assertSame( 14.75, $by_id[201]->rating );
        $this->assertNull( $by_id[202]->rating );
        $this->assertSame( 0.0, $by_id[203]->rating );

        $this->assertSame( '07700 900123', $by_id[201]->contactno );
        $this->assertSame( 'captain@example.com', $by_id[201]->contactemail );
        $this->assertSame( 'W', $by_id[201]->league_status );
        $this->assertSame( 55, $by_id[201]->captain_id );

        $this->assertNull( $by_id[202]->contactno );
        $this->assertNull( $by_id[202]->contactemail );
        $this->assertNull( $by_id[202]->league_status );

        $this->assertNull( $by_id[203]->contactno );
        $this->assertNull( $by_id[203]->contactemail );
        $this->assertNull( $by_id[203]->league_status );

        $this->assertNull( $by_id[204]->contactno );
        $this->assertNull( $by_id[204]->contactemail );
        $this->assertNull( $by_id[204]->league_status );
    }

    public function test_event_query_service_get_clubs_casts_string_counts_to_integers(): void {
        $event_raw = (object) array(
            'id'             => 5,
            'name'           => 'Knockout Event',
            'type'           => 'singles',
            'competition_id' => 1,
            'seasons'        => array( array( 'name' => '2026' ) ),
        );
        $competition = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( array( 'mode' => 'league' ) );
        $competition->type = Competition_Type::LEAGUE;

        $event = Event::from_object( $event_raw, $competition );
        $event->current_season = array( 'name' => '2026' );

        $club_dto = new \Racketmanager\Domain\DTO\Club\Club_Hydration_DTO(
            id: 7,
            name: 'Park Tennis Club',
            shortcode: 'PTC'
        );
        $club_obj = new Club( $club_dto );

        $event_repo = $this->createMock( Event_Repository_Interface::class );
        $event_repo->expects( $this->once() )
            ->method( 'query_clubs' )
            ->willReturn( array(
                (object) array(
                    'club_id'    => 7,
                    'team_count' => '14',
                ),
            ) );
        $event_repo->method( 'query_players' )
            ->willReturn( 48 );

        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $club_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 7 )
            ->willReturn( $club_obj );

        $player_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );

        $service = new Event_Query_Service(
            event_repository: $event_repo,
            club_repository: $club_repo,
            player_repository: $player_repo
        );

        $clubs = $service->get_clubs( $event );

        $this->assertCount( 1, $clubs );
        $this->assertSame( 14, $clubs[0]->team_count );
        $this->assertSame( 48, $clubs[0]->player_count );
    }
}
