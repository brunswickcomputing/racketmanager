<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Competition\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Entry_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

/**
 * League and cup entry flows record entered events on the Club aggregate through its domain setter.
 */
class Get_Competition_Entry_Handler_Club_Entry_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function make_event(): Event {
        return new class() extends Event {
            public function __construct() {
                $this->set_id( 101 );
                $this->set_name( 'Men Premier' );
                $this->set_type( 'MD' );
                $this->set_status( '' );
                $this->set_teams( [] );
                $this->set_event_teams( [] );
            }
            public function get_id(): int {
                return 101;
            }
        };
    }

    private function make_event_team( string $profile ): stdClass {
        $event_team            = new stdClass();
        $event_team->team_id   = 7;
        $event_team->name      = 'Leigh 1';
        $event_team->profile   = $profile;
        $event_team->status    = null;
        $event_team->league_id = 3;
        return $event_team;
    }

    private function build_handler( Competition_Type $type, $club, Event $event, array $event_teams ): Get_Competition_Entry_Handler {
        $club_service = $this->createStub( Club_Service_Interface::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $player_service = $this->createStub( Player_Service_Interface::class );
        $comp_service   = $this->createStub( Competition_Service_Interface::class );

        $competition = Competition_Test_Factory::create(
            id: 12,
            name: 'Summer Competition',
            type: $type,
            seasons: [ '2025' => [ 'name' => '2025' ] ],
            current_season: [ 'name' => '2025' ],
        );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [ $event ] );

        $team_service = $this->createStub( Team_Service_Interface::class );
        $team_service->method( 'get_teams_for_club' )->willReturn( [] );

        $event_query = $this->createStub( Event_Query_Service_Interface::class );
        $event_query->method( 'get_teams' )->willReturn( $event_teams );
        $event_query->method( 'get_team_info' )->willReturn( null );

        $selection = $this->createStub( Club_Entry_Selection_Service::class );
        $selection->method( 'available_clubs' )->willReturn( true );

        return new Get_Competition_Entry_Handler(
            club_entry_selection: $selection,
            competition_service: $comp_service,
            tournament_service: $this->createStub( Tournament_Service_Interface::class ),
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $this->createStub( Registration_Service_Interface::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service_Interface::class ),
            finance_service: $this->createStub( Finance_Service_Interface::class ),
            event_query_service: $event_query
        );
    }

    private function query(): Get_Competition_Entry_Query {
        return new Get_Competition_Entry_Query(
            competition_name: 'Summer Competition',
            season: '2025',
            club_name: 'Leigh TC',
            user_id: 1,
            is_admin: true,
            entry_level: 'captain'
        );
    }

    public function test_league_entry_marks_event_as_entered_on_real_club(): void {
        $club    = Club_Test_Factory::create( id: 5, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $event   = $this->make_event();
        $handler = $this->build_handler( Competition_Type::LEAGUE, $club, $event, [ $this->make_event_team( '1' ) ] );

        $result = $handler->handle( $this->query() );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'league', $result->type );
        $this->assertSame( [ 101 => true ], $club->entry );
    }

    public function test_league_entry_leaves_club_entry_untouched_when_no_event_is_entered(): void {
        $club    = Club_Test_Factory::create( id: 5, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $event   = $this->make_event();
        $handler = $this->build_handler( Competition_Type::LEAGUE, $club, $event, [ $this->make_event_team( 'x' ) ] );

        $result = $handler->handle( $this->query() );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( [], $club->entry );
    }

    public function test_league_entry_preserves_existing_club_entries(): void {
        $club = Club_Test_Factory::create( id: 5, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $club->set_entry( [ 999 => true ] );
        $event   = $this->make_event();
        $handler = $this->build_handler( Competition_Type::LEAGUE, $club, $event, [ $this->make_event_team( '0' ) ] );

        $handler->handle( $this->query() );

        $this->assertSame( [ 999 => true, 101 => true ], $club->entry );
    }

    public function test_cup_entry_stores_checked_event_on_real_club(): void {
        $club    = Club_Test_Factory::create( id: 5, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $event   = $this->make_event();
        $handler = $this->build_handler( Competition_Type::CUP, $club, $event, [ $this->make_event_team( '1' ) ] );

        $result = $handler->handle( $this->query() );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'cup', $result->type );
        $this->assertSame( [ 101 ], array_keys( $club->entry ) );
        $this->assertSame( $event, $club->entry[101] );
        $this->assertSame( 'checked', $event->status );
    }

    public function test_cup_entry_leaves_club_entry_untouched_without_matching_teams(): void {
        $club    = Club_Test_Factory::create( id: 5, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $event   = $this->make_event();
        $handler = $this->build_handler( Competition_Type::CUP, $club, $event, [] );

        $handler->handle( $this->query() );

        $this->assertSame( [], $club->entry );
    }
}
