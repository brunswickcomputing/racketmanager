<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Wordpress\Request\Competition\League_Entry_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Invoice_Details_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;

#[AllowMockObjectsWithoutExpectations]
class Competition_Entry_Service_Test extends TestCase {
    use Creates_View_Renderer;
    use Manages_Wordpress_Globals;

    private Competition_Entry_Service $service;
    private Competition_Service $competition_service;
    private Event_Repository_Interface|MockObject $event_repository;
    private Event_Query_Service $event_query_service;
    private Team_Repository|MockObject $team_repository;
    private Club_Repository $club_repository;
    private League_Team_Repository|MockObject $league_team_repository;
    private Tournament_Entry_Repository|MockObject $tournament_entry_repository;
    private Player_Service|MockObject $player_service;
    /** @var array<string, array<int|string, Player>> players the Validator finds, keyed by search term then value. */
    private array $validator_players = [];
    private Notify_Service $notify_service;
    private Tournament_Repository $tournament_repository;
    private Tournament_Service $tournament_service;
    private Club_Service $club_service;
    private Finance_Service|MockObject $finance_service;
    private RacketManager $plugin;
    private ?Club $club_to_return;
    private ?Competition $competition_to_return;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_options'] = [
            'racketmanager' => [],
        ];

        $this->plugin = $this->createStub( RacketManager::class );
        $this->plugin->site_name = 'Test Site';
        $this->plugin->method( 'get_confirmation_email' )->willReturn( 'league@test.local' );

        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->competition_to_return = null;
        $this->competition_service->method( 'get_by_id' )->willReturnCallback( fn() => $this->competition_to_return );
        $this->event_repository = $this->createMock( Event_Repository_Interface::class );
        $this->event_query_service = new Event_Query_Service(
            $this->event_repository,
            null,
            null,
            $this->createStub( League_Repository::class )
        );
        $this->team_repository = $this->createMock( Team_Repository::class );
        $this->club_repository = $this->createStub( Club_Repository::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository::class );
        $this->player_service = $this->createMock( Player_Service::class );
        $this->notify_service = $this->createStub( Notify_Service::class );
        $this->tournament_repository = $this->createStub( Tournament_Repository::class );
        $this->tournament_service = $this->createStub( Tournament_Service::class );
        $this->club_service = $this->createStub( Club_Service::class );
        $this->finance_service = $this->createMock( Finance_Service::class );

        $container = new Simple_Container();
        $container->set( 'competition_service', $this->competition_service );
        $container->set( 'event_repository', $this->event_repository );
        $container->set( 'player_service', $this->player_service );
        $this->validator_players = [];
        $player_repository       = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturnCallback(
            fn( $value, string $search_term = 'id' ): ?Player => $this->validator_players[ $search_term ][ $value ] ?? null
        );
        $container->set( 'player_repository', $player_repository );
        $this->plugin->container = $container;
        $GLOBALS['racketmanager'] = $this->plugin;

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Test Club' );
        $club->set_shortcode( 'TC' );
        $this->club_to_return = $club;
        $this->club_repository->method( 'find_by_id' )->willReturnCallback( fn() => $this->club_to_return );
        $this->club_service->method( 'get_club' )->willReturnCallback( fn( $cid = null ) => ! empty( $cid ) ? $this->club_to_return : throw new Club_Not_Found_Exception( Util_Messages::club_not_found( (int) $cid ) ) );

        $this->tournament_entry_repository = $this->createMock( Tournament_Entry_Repository::class );

        $this->service = $this->create_competition_entry_service();
    }

    private function create_competition_entry_service(): Competition_Entry_Service {
        return new Competition_Entry_Service(
            plugin_instance: $this->plugin,
            club_repository: $this->club_repository,
            league_repository: $this->createStub( League_Repository::class ),
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            tournament_repository: $this->tournament_repository,
            tournament_entry_repository: $this->tournament_entry_repository,
            club_service: $this->club_service,
            competition_service: $this->competition_service,
            finance_service: $this->finance_service,
            player_service: $this->player_service,
            tournament_service: $this->tournament_service,
            notify_service: $this->notify_service,
            event_query_service: $this->event_query_service
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_competition( array $settings = [] ): Competition {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026', [
                'date_open'    => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
                'date_closing' => gmdate( 'Y-m-d', strtotime( '+30 days' ) ),
                'date_start'   => gmdate( 'Y-m-d', strtotime( '+60 days' ) ),
                'date_end'     => gmdate( 'Y-m-d', strtotime( '+180 days' ) ),
            ] ),
            settings: array_merge( [
                'match_day_restriction' => '1',
                'match_day_weekends' => '1',
            ], $settings )
        );
        $this->competition_to_return = $competition;
        return $competition;
    }

    private function assert_league_entry_error( mixed $result, string $code, ?string $message = null ): void {
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( $code, $result->get_error_codes() );
        $this->assertNotContains( 'acceptance', $result->get_error_codes() );
        $this->assertNotContains( 'Competition not open for entries', $result->get_error_messages() );
        if ( null !== $message ) {
            $this->assertContains( $message, $result->get_error_messages() );
        }
    }

    private function create_event( int $id, string $type = 'MD', int $num_rubbers = 4 ): Event {
        $raw = new stdClass();
        $raw->id = $id;
        $raw->name = 'Mens Doubles Event ' . $id;
        $raw->type = $type;
        $raw->num_rubbers = $num_rubbers;
        $raw->settings = array( 'match_days_allowed' => [ '1' => '1', '2' => '1', '5' => '1', '6' => '1' ] );
        $raw->seasons = '[]';
        $raw->competition_id = 1;
        return Event::from_object( $raw );
    }

    private function create_mock_team( int $id, string $title ): Team {
        $team = new Team();
        $team->set_id( $id );
        $team->title = $title;
        return $team;
    }

    private function create_league_entry_data( array $overrides = [] ): array {
        return array_replace( [
            'competitionId'        => '1',
            'season'               => '2026',
            'clubId'               => '1',
            'acceptance'           => '1',
            'numCourtsAvailable'   => '4',
            'competition_events'   => '10',
            'event'                => [ 10 ],
            'event_teams'          => [ 10 => '101,102' ],
            'teamEvent'            => [ 10 => [ 101, 102 ] ],
            'teamEventTitle'       => [ 10 => [ 101 => 'Club Team 1', 102 => 'Club Team 2' ] ],
            'captain'              => [ 10 => [ 101 => 'John', 102 => 'Jane' ] ],
            'captainId'            => [ 10 => [ 101 => '1', 102 => '2' ] ],
            'contactno'            => [ 10 => [ 101 => '0123456789', 102 => '0987654321' ] ],
            'contactemail'         => [ 10 => [ 101 => 'john@test.local', 102 => 'jane@test.local' ] ],
            'matchday'             => [ 10 => [ 101 => '1', 102 => '2' ] ],
            'matchtime'            => [ 10 => [ 101 => '19:00:00', 102 => '19:00:00' ] ],
        ], $overrides );
    }

    private function create_single_team_league_entry_data( int $team_id = 101, string $team_title = 'Club Team 1', array $overrides = [] ): array {
        return array_replace( [
            'competitionId'        => '1',
            'season'               => '2026',
            'clubId'               => '1',
            'acceptance'           => '1',
            'numCourtsAvailable'   => '4',
            'competition_events'   => '10',
            'event'                => [ 10 ],
            'event_teams'          => [ 10 => (string) $team_id ],
            'teamEvent'            => [ 10 => [ $team_id ] ],
            'teamEventTitle'       => [ 10 => [ $team_id => $team_title ] ],
            'captain'              => [ 10 => [ $team_id => 'John' ] ],
            'captainId'            => [ 10 => [ $team_id => '1' ] ],
            'contactno'            => [ 10 => [ $team_id => '0123456789' ] ],
            'contactemail'         => [ 10 => [ $team_id => 'john@test.local' ] ],
            'matchday'             => [ 10 => [ $team_id => '1' ] ],
            'matchtime'            => [ 10 => [ $team_id => '19:00:00' ] ],
        ], $overrides );
    }

    private function setup_mock_teams( Team ...$teams ): void {
        $map = [];
        foreach ( $teams as $team ) {
            $map[ $team->get_id() ] = $team;
        }
        $this->team_repository->method( 'find_by_id' )
            ->willReturnCallback( fn( $id ) => $map[ (int) $id ] ?? null );
    }

    private function setup_match_secretary_and_notifications(): void {
        $this->player_service->method( 'update_contact_details' );
        $secretary = $this->createStub( Player::class );
        $secretary->display_name = 'Match Secretary';
        $secretary->email = 'secretary@test.local';
        $this->player_service->method( 'get_match_secretary_details' )->willReturn( $secretary );
        $this->notify_service->method( 'email_entry_form' );
    }

    private function create_tournament_entry_request( array $overrides = [] ): Tournament_Entry_Request_DTO {
        $data = array_merge( [
            'tournament_id'    => '1',
            'season'           => '2026',
            'playerId'         => '10',
            'clubId'           => '1',
            'contactemail'     => 'player@test.local',
            'contactno'        => '0123456789',
            'btm'              => '12345',
            'acceptance'       => '1',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ], $overrides );
        return new Tournament_Entry_Request_DTO( $data );
    }

    public function test_request_league_entry_flags_withdrawn_team_hierarchy_violation(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        $post_data = $this->create_league_entry_data( [
            'teamEvent'      => [ 10 => [ 102 ] ], // Withdrawing Team 1, keeping Team 2
            'teamEventTitle' => [ 10 => [ 102 => 'Club Team 2' ] ],
            'captain'        => [ 10 => [ 102 => 'John Doe' ] ],
            'captainId'      => [ 10 => [ 102 => '1' ] ],
            'contactno'      => [ 10 => [ 102 => '0123456789' ] ],
            'contactemail'   => [ 10 => [ 102 => 'john@test.local' ] ],
            'matchday'       => [ 10 => [ 102 => '1' ] ],
            'matchtime'      => [ 10 => [ 102 => '19:00:00' ] ],
        ] );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error(
            $result,
            'teamEvent-10-102',
            'A higher ranked team cannot be withdrawn if there are still active lower ranked teams'
        );
    }

    public function test_request_league_entry_flags_weekend_match_ranking_violation(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        // Team 1 is playing on weekend (day 5 / Sat), but Team 2 is on weekday (day 1 / Mon)
        $post_data = $this->create_league_entry_data( [
            'matchday'  => [ 10 => [ 101 => '5', 102 => '1' ] ],
            'matchtime' => [ 10 => [ 101 => '14:00:00', 102 => '19:00:00' ] ],
        ] );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error(
            $result,
            'matchday-10-101',
            'Only the highest numbered team(s) can play at the weekend'
        );
    }

    public function test_request_league_entry_flags_match_time_overlap(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        // Two teams on the same day with overlapping times (19:00 vs 19:30)
        $post_data = $this->create_league_entry_data( [
            'matchday'  => [ 10 => [ 101 => '1', 102 => '1' ] ],
            'matchtime' => [ 10 => [ 101 => '19:00:00', 102 => '19:30:00' ] ],
        ] );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error( $result, 'matchtime-10-102', 'Match overlap' );
    }

    public function test_request_league_entry_flags_free_slots_when_weekend_used_with_empty_weekdays(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        // 1 team playing on weekend (day 5), but weekdays (days 1, 2) have 0 teams (underfilled capacity)
        $post_data = $this->create_single_team_league_entry_data( 101, 'Club Team 1', [
            'matchday'  => [ 10 => [ 101 => '5' ] ],
            'matchtime' => [ 10 => [ 101 => '14:00:00' ] ],
        ] );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error(
            $result,
            'event',
            'Weekend games not allowed when free weekday slots'
        );
    }

    public function test_forged_post_title_cannot_bypass_team_number_rules(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        $post_data = $this->create_league_entry_data( [
            'teamEventTitle' => [ 10 => [ 101 => 'Club Team 99', 102 => 'Club Team 2' ] ], // Forged title attempting bypass
            'matchday'       => [ 10 => [ 101 => '5', 102 => '1' ] ],
            'matchtime'      => [ 10 => [ 101 => '14:00:00', 102 => '19:00:00' ] ],
        ] );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        // Must still detect that persisted team 101 is Team 1 and fail the weekend check
        $this->assert_league_entry_error( $result, 'matchday-10-101' );
    }

    public function test_valid_league_entry_request_passes_validation(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        $post_data = $this->create_league_entry_data();

        $league_team = $this->createMock( League_Team::class );
        $league_team->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $this->league_team_repository->method( 'find_by_event_id' )
            ->willReturnCallback( function( $event_id, $season, $team_id ) use ( $league_team ) {
                $this->assertSame( 10, $event_id );
                $this->assertSame( 2026, $season );
                $this->assertContains( (int) $team_id, [ 101, 102 ] );
                return [ $league_team ];
            } );
        $this->league_team_repository->expects( $this->exactly( 2 ) )->method( 'save' );
        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_unresolved_team_id_is_rejected(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->team_repository->method( 'find_by_id' )->willReturn( null );

        $post_data = $this->create_single_team_league_entry_data( 999, 'Forged Team 1' );
        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error( $result, 'teamEvent-10-999', 'Team not found' );
    }

    public function test_unknown_club_is_rejected(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->club_to_return = null;
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data( 101, 'Club Team 1', [ 'clubId' => '999' ] );
        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error( $result, 'club', 'Club not found' );
        $this->assertSame( [ 'club' ], $result->get_error_codes() );
        $this->assertSame( [ 'Club not found' ], $result->get_error_messages() );
    }

    public function test_weekend_teams_across_multiple_events_of_same_type_enforces_ranking(): void {
        $this->create_competition();
        $event1 = $this->create_event( 10, 'MD' );
        $event2 = $this->create_event( 20, 'MD' );

        $this->competition_service->method( 'get_event_by_id' )->willReturnCallback( function( $id ) use ( $event1, $event2 ) {
            return match ( (int) $id ) {
                10 => $event1,
                20 => $event2,
                default => null,
            };
        } );

        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ), $this->create_mock_team( 102, 'Club Team 2' ) );

        // Event 10 has Team 2 on weekday (Mon / day 1), Event 20 has Team 1 on weekend (Sat / day 5)
        $post_data = [
            'competitionId'        => '1',
            'season'               => '2026',
            'clubId'               => '1',
            'acceptance'           => '1',
            'numCourtsAvailable'   => '4',
            'competition_events'   => '10,20',
            'event'                => [ 10, 20 ],
            'event_teams'          => [ 10 => '102', 20 => '101' ],
            'teamEvent'            => [ 10 => [ 102 ], 20 => [ 101 ] ],
            'teamEventTitle'       => [ 10 => [ 102 => 'Club Team 2' ], 20 => [ 101 => 'Club Team 1' ] ],
            'captain'              => [ 10 => [ 102 => 'Jane' ], 20 => [ 101 => 'John' ] ],
            'captainId'            => [ 10 => [ 102 => '2' ], 20 => [ 101 => '1' ] ],
            'contactno'            => [ 10 => [ 102 => '0987654321' ], 20 => [ 101 => '0123456789' ] ],
            'contactemail'         => [ 10 => [ 102 => 'jane@test.local' ], 20 => [ 101 => 'john@test.local' ] ],
            'matchday'             => [ 10 => [ 102 => '1' ], 20 => [ 101 => '5' ] ],
            'matchtime'            => [ 10 => [ 102 => '19:00:00' ], 20 => [ 101 => '14:00:00' ] ],
        ];

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error(
            $result,
            'matchday-20-101',
            'Only the highest numbered team(s) can play at the weekend'
        );
    }

    public function test_highest_numbered_team_is_allowed_to_play_at_the_weekend(): void {
        $this->create_competition();
        $event = $this->create_event( 10 );
        $event->match_days_allowed = [ '1' => '1', '5' => '1' ];
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->setup_mock_teams(
            $this->create_mock_team( 101, 'Club Team 1' ),
            $this->create_mock_team( 102, 'Club Team 2' ),
            $this->create_mock_team( 103, 'Club Team 3' )
        );

        $post_data = $this->create_league_entry_data( [
            'numCourtsAvailable' => '8',
            'event_teams'        => [ 10 => '101,102,103' ],
            'teamEvent'          => [ 10 => [ 101, 102, 103 ] ],
            'teamEventTitle'     => [ 10 => [ 101 => 'Club Team 1', 102 => 'Club Team 2', 103 => 'Club Team 3' ] ],
            'captain'            => [ 10 => [ 101 => 'John', 102 => 'Jane', 103 => 'Alex' ] ],
            'captainId'          => [ 10 => [ 101 => '1', 102 => '2', 103 => '3' ] ],
            'contactno'          => [ 10 => [ 101 => '0123456789', 102 => '0987654321', 103 => '0111222333' ] ],
            'contactemail'       => [ 10 => [ 101 => 'john@test.local', 102 => 'jane@test.local', 103 => 'alex@test.local' ] ],
            'matchday'           => [ 10 => [ 101 => '1', 102 => '1', 103 => '5' ] ],
            'matchtime'          => [ 10 => [ 101 => '19:00:00', 102 => '19:00:00', 103 => '14:00:00' ] ],
        ] );

        $league_team = $this->createMock( League_Team::class );
        $league_team->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $this->league_team_repository->method( 'find_by_event_id' )
            ->willReturnCallback( function( $event_id, $season, $team_id ) use ( $league_team ) {
                $this->assertSame( 10, $event_id );
                $this->assertSame( 2026, $season );
                $this->assertContains( (int) $team_id, [ 101, 102, 103 ] );
                return [ $league_team ];
            } );
        $this->league_team_repository->expects( $this->exactly( 3 ) )->method( 'save' );
        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 8 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_team_registered_in_conflicting_divisions_is_rejected(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $league_team_div1 = $this->createMock( League_Team::class );
        $league_team_div1->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_div1->method( 'get_league_id' )->willReturn( 1001 );

        $league_team_div2 = $this->createMock( League_Team::class );
        $league_team_div2->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_div2->method( 'get_league_id' )->willReturn( 1002 );

        $this->league_team_repository->expects( $this->once() )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $league_team_div1, $league_team_div2 ] );

        $this->league_team_repository->expects( $this->never() )->method( 'save' );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assert_league_entry_error(
            $result,
            'teamEvent-10-101',
            'Team is already registered in conflicting divisions'
        );
    }

    public function test_legitimate_single_returning_team_is_accepted_without_conflict(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $league_team_div1 = $this->createMock( League_Team::class );
        $league_team_div1->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_div1->method( 'get_league_id' )->willReturn( 1001 );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $league_team_div1 ] );

        $this->league_team_repository->expects( $this->once() )->method( 'save' )->with( $league_team_div1 );
        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_withdrawn_and_active_registration_in_different_divisions_is_not_flagged_as_conflict(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $league_team_active = $this->createMock( League_Team::class );
        $league_team_active->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_active->method( 'get_league_id' )->willReturn( 1001 );

        $league_team_withdrawn = $this->createMock( League_Team::class );
        $league_team_withdrawn->method( 'get_profile_enum' )->willReturn( Team_Profile::WITHDRAWN );
        $league_team_withdrawn->method( 'get_league_id' )->willReturn( 1002 );
        $league_team_withdrawn->expects( $this->never() )->method( 'set_entered_state' );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $league_team_active, $league_team_withdrawn ] );

        $this->league_team_repository->expects( $this->once() )->method( 'save' )->with( $league_team_active );
        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_multiple_active_rows_in_same_division_are_not_flagged_as_conflicting_divisions(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $league_team_row1 = $this->createMock( League_Team::class );
        $league_team_row1->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_row1->method( 'get_league_id' )->willReturn( 1001 );

        $league_team_row2 = $this->createMock( League_Team::class );
        $league_team_row2->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $league_team_row2->method( 'get_league_id' )->willReturn( 1001 );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $league_team_row1, $league_team_row2 ] );

        $this->league_team_repository->expects( $this->exactly( 2 ) )->method( 'save' );
        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_single_pending_returning_team_is_promoted_to_active_and_persisted(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $pending_team_row = (object) [
            'id'        => 101,
            'league_id' => 1001,
            'profile'   => 0, // Team_Profile::PENDING
        ];
        $pending_league_team = League_Team::from_object( $pending_team_row );

        $this->assertSame( Team_Profile::PENDING, $pending_league_team->get_profile_enum() );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $pending_league_team ] );

        $this->league_team_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $pending_league_team );

        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
        $this->assertSame( Team_Profile::ACTIVE, $pending_league_team->get_profile_enum() );
    }

    public function test_multiple_withdrawn_rows_in_different_divisions_reactivates_only_one_row(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $withdrawn_mock_1 = $this->createMock( League_Team::class );
        $withdrawn_mock_1->method( 'get_profile_enum' )->willReturn( Team_Profile::WITHDRAWN );
        $withdrawn_mock_1->method( 'get_league_id' )->willReturn( 1001 );
        $withdrawn_mock_1->expects( $this->once() )->method( 'set_entered_state' )->with( Team_Profile::ACTIVE );

        $withdrawn_mock_2 = $this->createMock( League_Team::class );
        $withdrawn_mock_2->method( 'get_profile_enum' )->willReturn( Team_Profile::WITHDRAWN );
        $withdrawn_mock_2->method( 'get_league_id' )->willReturn( 1002 );
        $withdrawn_mock_2->expects( $this->never() )->method( 'set_entered_state' );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->with( 10, 2026, 101 )
            ->willReturn( [ $withdrawn_mock_1, $withdrawn_mock_2 ] );

        $this->league_team_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $withdrawn_mock_1 );

        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_prior_season_conflicting_rows_do_not_affect_current_season_entry(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams( $this->create_mock_team( 101, 'Club Team 1' ) );

        $post_data = $this->create_single_team_league_entry_data();

        $prior_season_div1 = $this->createMock( League_Team::class );
        $prior_season_div1->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $prior_season_div1->method( 'get_league_id' )->willReturn( 1001 );

        $prior_season_div2 = $this->createMock( League_Team::class );
        $prior_season_div2->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $prior_season_div2->method( 'get_league_id' )->willReturn( 1002 );

        $current_season_team = $this->createMock( League_Team::class );
        $current_season_team->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $current_season_team->method( 'get_league_id' )->willReturn( 1001 );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_event_id' )
            ->willReturnCallback( function( $event_id, $season, $team_id ) use ( $prior_season_div1, $prior_season_div2, $current_season_team ) {
                $this->assertSame( 10, $event_id );
                $this->assertSame( 101, (int) $team_id );
                if ( 2025 === $season ) {
                    return [ $prior_season_div1, $prior_season_div2 ];
                }
                if ( 2026 === $season ) {
                    return [ $current_season_team ];
                }
                return [];
            } );

        $this->league_team_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $current_season_team );

        $this->competition_service->expects( $this->once() )->method( 'set_court_availability' )->with( 1, 1, 4 );
        $this->setup_match_secretary_and_notifications();

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertTrue( $result );
    }

    public function test_multi_error_on_same_field_does_not_shift_messages_in_league_entry(): void {
        $this->create_competition();
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $this->create_event( 10 ) );
        $this->setup_mock_teams(
            $this->create_mock_team( 100, 'Club Team 0' ),
            $this->create_mock_team( 101, 'Club Team 1' ),
            $this->create_mock_team( 102, 'Club Team 2' )
        );

        // Post data: team 101 (Team 1, day 5 weekend) and team 102 (Team 2, day 1 weekday).
        // Missed team 100 (Team 0) was withdrawn while team 101 is active.
        // Also team 101 has active rows in 2 divisions (duplicate).
        $post_data = $this->create_league_entry_data( [
            'missed_teams' => [ 10 => [ '100' ] ],
            'matchday'     => [ 10 => [ 101 => '5', 102 => '1' ] ],
        ] );

        $active_div1 = $this->createMock( League_Team::class );
        $active_div1->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $active_div1->method( 'get_league_id' )->willReturn( 1001 );

        $active_div2 = $this->createMock( League_Team::class );
        $active_div2->method( 'get_profile_enum' )->willReturn( Team_Profile::ACTIVE );
        $active_div2->method( 'get_league_id' )->willReturn( 1002 );

        $this->league_team_repository->method( 'find_by_event_id' )
            ->willReturnCallback( function( $event_id, $season, $team_id ) use ( $active_div1, $active_div2 ) {
                if ( (int) $team_id === 101 ) {
                    return [ $active_div1, $active_div2 ];
                }
                return [ $active_div1 ];
            } );

        $result = $this->service->request_league_entry( new League_Entry_Request_DTO( $post_data ) );

        $this->assertInstanceOf( WP_Error::class, $result );
        $codes = $result->get_error_codes();
        $messages = $result->get_error_messages();

        // Ensure 1-to-1 matching between unique codes and messages (no message shift)
        $this->assertSame( count( $codes ), count( $messages ) );
        $this->assertContains( 'teamEvent-10-101', $codes );
        $this->assertContains( 'matchday-10-101', $codes );
        $this->assertSame( 'Team is already registered in conflicting divisions', $result->get_error_message( 'teamEvent-10-101' ) );
        $this->assertSame( 'Only the highest numbered team(s) can play at the weekend', $result->get_error_message( 'matchday-10-101' ) );
        $this->assertSame( [ 'status' => 400 ], $result->get_error_data( 'teamEvent-10-101' ) );
    }

    private function create_tournament( int $id = 1, string $season = '2026', string $end_date = '2026-08-30' ): Tournament {
        $this->create_competition();
        $raw = new stdClass();
        $raw->id = $id;
        $raw->name = 'Summer Tournament';
        $raw->season = $season;
        $raw->date_open = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        $raw->date_closing = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
        $raw->date_start = gmdate( 'Y-m-d', strtotime( '+60 days' ) );
        $raw->date_end = $end_date;
        $raw->competition_id = 1;
        $raw->num_entries = 4;
        $raw->link = '/tournament/summer-2026';
        $raw->entry_link = '/tournament/summer-2026/entry';
        $tournament = Tournament::from_object( $raw );
        $tournament->is_open = true;
        $this->tournament_repository->method( 'find_by_id' )->willReturnCallback( fn( $tid ) => (int) $tid === $id ? $tournament : null );
        return $tournament;
    }

    private function create_tournament_player( int $id, string $gender, int $yob, string $email = 'player@test.local' ): Player {
        return Player::from_array( [
            'ID'            => $id,
            'gender'        => $gender,
            'year_of_birth' => (string) $yob,
            'display_name'  => 'Player ' . $id,
            'email'         => $email,
            'contactno'     => '0123456789',
            'opt_ins'       => [],
        ] );
    }

    private function create_tournament_event( int $id, string $type, ?int $age_limit = null, int $age_offset = 0 ): Event {
        $raw = new stdClass();
        $raw->id = $id;
        $raw->name = 'Tournament Event ' . $id;
        $raw->type = $type;
        $raw->num_rubbers = 1;
        $raw->seasons = '[]';
        $raw->competition_id = 1;
        $raw->settings = array(
            'age_limit'          => $age_limit,
            'age_offset'         => $age_offset,
            'match_days_allowed' => [ '1' => '1', '2' => '1' ],
            'primary_league'     => 1000 + $id,
            'league_index'       => [ ( 1000 + $id ) => '1' ],
        );
        return Event::from_object( $raw );
    }

    public function test_tournament_entry_rejects_man_41_in_senior_45_event(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1985 ); // age 41 in 2026
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : null );

        $event = $this->create_tournament_event( 10, 'SS', 45, 0 ); // Senior Singles 45+
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $dto = $this->create_tournament_entry_request( [
            'playerId'     => '10',
            'contactemail' => 'man@test.local',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertContains( 'One or more selected events are not available for this season', $result->get_error_messages() );
    }

    public function test_tournament_entry_accepts_woman_41_in_senior_45_event_with_5y_offset(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 20, 'F', 1985 ); // age 41 in 2026
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 20 ? $player : null );

        $event = $this->create_tournament_event( 10, 'SS', 45, 5 ); // Senior Singles 45+ with 5y offset (min age 40 <= 41)
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        $dto = $this->create_tournament_entry_request( [
            'playerId'     => '20',
            'contactemail' => 'woman@test.local',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
    }

    public function test_tournament_entry_accepts_senior_and_open_events_for_both_genders(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event_open = $this->create_tournament_event( 10, 'OS', null, 0 ); // Open Singles
        $event_senior = $this->create_tournament_event( 11, 'SS', 40, 0 ); // Senior Singles 40+

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event_open, $event_senior ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnCallback( fn( $id ) => (int) $id === 10 ? $event_open : $event_senior );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 25.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        // 1. Male age 42 (born 1984) enters Open and Senior
        $male_player = $this->create_tournament_player( 30, 'M', 1984, 'male@test.local' );
        $female_player = $this->create_tournament_player( 31, 'F', 1984, 'female@test.local' );
        $this->player_service->method( 'get_player' )->willReturnCallback( function( $pid ) use ( $male_player, $female_player ) {
            return match( (int) $pid ) {
                30 => $male_player,
                31 => $female_player,
                default => null,
            };
        } );

        $dto_male = $this->create_tournament_entry_request( [
            'playerId'         => '30',
            'contactemail'     => 'male@test.local',
            'tournamentEvents' => '10,11',
            'event'            => [ '10', '11' ],
        ] );
        $res_male = $this->service->request_tournament_entry( $dto_male );
        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $res_male );

        // 2. Female age 42 (born 1984) enters Open and Senior
        $dto_female = $this->create_tournament_entry_request( [
            'playerId'         => '31',
            'contactemail'     => 'female@test.local',
            'tournamentEvents' => '10,11',
            'event'            => [ '10', '11' ],
        ] );
        $res_female = $this->service->request_tournament_entry( $dto_female );
        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $res_female );
    }

    public function test_tournament_entry_rejects_wrong_gender_events(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event_women = $this->create_tournament_event( 10, 'WS' ); // Women Singles
        $event_men = $this->create_tournament_event( 11, 'MS' );   // Men Singles

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event_women, $event_men ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnCallback( fn( $id ) => (int) $id === 10 ? $event_women : $event_men );

        // 1. Male entering Women Singles -> rejected
        $male_player = $this->create_tournament_player( 40, 'M', 1990 );
        $female_player = $this->create_tournament_player( 41, 'F', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( function( $pid ) use ( $male_player, $female_player ) {
            return match( (int) $pid ) {
                40 => $male_player,
                41 => $female_player,
                default => null,
            };
        } );

        $dto_male = $this->create_tournament_entry_request( [
            'playerId'     => '40',
            'contactemail' => 'male@test.local',
            'event'        => [ '10' ],
        ] );
        $res_male = $this->service->request_tournament_entry( $dto_male );
        $this->assertInstanceOf( WP_Error::class, $res_male );
        $this->assertContains( 'event', $res_male->get_error_codes() );

        // 2. Female entering Men Singles -> rejected
        $dto_female = $this->create_tournament_entry_request( [
            'playerId'         => '41',
            'contactemail'     => 'female@test.local',
            'tournamentEvents' => '11',
            'event'            => [ '11' ],
        ] );
        $res_female = $this->service->request_tournament_entry( $dto_female );
        $this->assertInstanceOf( WP_Error::class, $res_female );
        $this->assertContains( 'event', $res_female->get_error_codes() );
    }

    public function test_tournament_entry_rejects_foreign_event_outside_tournament(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event1 = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event1 ] );

        $player = $this->create_tournament_player( 50, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 50 ? $player : null );

        // Submits foreign event ID 999
        $dto = $this->create_tournament_entry_request( [
            'playerId' => '50',
            'event'    => [ '999' ],
        ] );

        $res = $this->service->request_tournament_entry( $dto );
        $this->assertInstanceOf( WP_Error::class, $res );
        $this->assertContains( 'event', $res->get_error_codes() );
    }

    public function test_tournament_entry_validates_doubles_partner_eligibility(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event_senior_doubles = $this->create_tournament_event( 10, 'SD', 45, 0 ); // Senior Doubles 45+

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event_senior_doubles ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event_senior_doubles );

        $player = $this->create_tournament_player( 60, 'M', 1975 ); // age 51 in 2026 (eligible)
        $partner_underage = $this->create_tournament_player( 61, 'M', 1995 ); // age 31 in 2026 (< 45, ineligible)
        $partner_eligible = $this->create_tournament_player( 62, 'M', 1978 ); // age 48 in 2026 (eligible)

        $this->player_service->method( 'get_player' )->willReturnCallback( function( $id ) use ( $player, $partner_underage, $partner_eligible ) {
            return match ( (int) $id ) {
                60 => $player,
                61 => $partner_underage,
                62 => $partner_eligible,
                default => null,
            };
        } );

        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        // 1. Submit with underage partner -> fails with partner-10 error
        $dto_underage = $this->create_tournament_entry_request( [
            'playerId'  => '60',
            'partnerId' => [ 10 => '61' ],
            'partner'   => [ 10 => 'Partner Underage' ],
        ] );

        $res_underage = $this->service->request_tournament_entry( $dto_underage );
        $this->assertInstanceOf( WP_Error::class, $res_underage );
        $this->assertContains( 'partner-10', $res_underage->get_error_codes() );
        $this->assertContains( 'Partner is too young', $res_underage->get_error_messages() );

        // 2. Submit with eligible partner -> passes
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        $dto_eligible = $this->create_tournament_entry_request( [
            'playerId'  => '60',
            'partnerId' => [ 10 => '62' ],
            'partner'   => [ 10 => 'Partner Eligible' ],
        ] );

        $res_eligible = $this->service->request_tournament_entry( $dto_eligible );
        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $res_eligible );
    }

    public function test_tournament_entry_does_not_withdraw_foreign_missed_events(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 70, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        // Forged tournamentEvents containing foreign event 999
        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '70',
            'tournamentEvents' => '10,999',
            'event'            => [ '10' ],
        ] );

        // We expect find_player_teams_by_player_for_events to NEVER be called with 999
        $this->league_team_repository->expects( $this->never() )
            ->method( 'find_player_teams_by_player_for_events' );

        $res = $this->service->request_tournament_entry( $dto );
        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $res );
    }

    public function test_tournament_entry_correctly_identifies_allowed_but_not_selected_event_as_missed(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        // Allow two events: 10 and 11
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $event11 = $this->create_tournament_event( 11, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10, $event11 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap([
            [10, $event10],
            [11, $event11]
        ]);

        $player = $this->create_tournament_player( 70, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 170 );

        // Only enter event 10
        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '70',
            'tournamentEvents' => '10,11',
            'event'            => [ '10' ],
        ] );

        // We expect find_player_teams_by_player_for_events to be called with 11
        $this->league_team_repository->expects( $this->once() )
            ->method( 'find_player_teams_by_player_for_events' )
            ->with( 70, [11], '2026' )
            ->willReturn( [] );

        $this->service->request_tournament_entry( $dto );
    }

    public function test_tournament_entry_rejects_unknown_gender(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event_open = $this->create_tournament_event( 10, 'OS', null, 0 );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event_open ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event_open );

        // Player with empty/unknown gender
        $player_unknown = $this->create_tournament_player( 80, '', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player_unknown );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '80',
        ] );

        $res = $this->service->request_tournament_entry( $dto );
        $this->assertInstanceOf( WP_Error::class, $res );
        $this->assertContains( 'event', $res->get_error_codes() );
    }

    public function test_tournament_entry_fails_closed_when_tournament_end_year_missing(): void {
        // Tournament with empty date_end, date_start, date_closing
        $tournament = $this->create_tournament( 1, '2026', '' );
        $tournament->date_start = '';
        $tournament->date_closing = '';

        $event_junior = $this->create_tournament_event( 10, 'BS', 18, 0 );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event_junior ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event_junior );

        $player = $this->create_tournament_player( 90, 'M', 2012 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '90',
        ] );

        $res = $this->service->request_tournament_entry( $dto );
        $this->assertInstanceOf( WP_Error::class, $res );
        $this->assertContains( 'event', $res->get_error_codes() );
    }

    public function test_tournament_entry_saves_player_opt_in_via_player_service(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10    = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 95, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        $this->player_service->expects( $this->once() )
            ->method( 'save_opt_in' )
            ->with( 95, '1' )
            ->willReturn( true );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '95',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $res = $this->service->request_tournament_entry( $dto );
        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $res );
        $this->assertContains( '1', $player->opt_ins );
    }

    public function test_tournament_entry_rejects_closed_tournament(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $tournament->is_open = false;
        $tournament->is_closed = false;
        $tournament->is_started = false;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $dto = $this->create_tournament_entry_request();
        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'tournament', $result->get_error_codes() );
        $this->assertContains( 'Tournament not open for entries', $result->get_error_messages() );
    }

    public function test_tournament_entry_allows_admin_when_tournament_started_but_not_open(): void {
        $tournament               = $this->create_tournament( 1, '2026', '2026-08-30' );
        $tournament->is_open      = false;
        $tournament->is_closed    = false;
        $tournament->is_started   = true;
        $tournament->date_closing = '2026-10-31';

        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        $GLOBALS['wp_stubs_current_user_can'] = true;

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '10',
            'event'    => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
    }

    public function test_tournament_entry_rejects_season_mismatch(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );

        $dto = $this->create_tournament_entry_request( [
            'season' => '2025',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'season', $result->get_error_codes() );
        $this->assertContains( 'Season does not match this tournament', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_nonexistent_tournament_id(): void {
        $this->tournament_repository->method( 'find_by_id' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'tournament_id' => '999',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertContains( Util_Messages::tournament_not_found( 999 ), $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_season_not_in_competition_seasons(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $tournament->competition_id = 1;

        $competition = $this->createStub( Competition::class );
        $competition->seasons = new Season_Collection( [ '2024' => [ 'name' => '2024' ], '2025' => [ 'name' => '2025' ] ] );
        $this->competition_to_return = $competition;

        $dto = $this->create_tournament_entry_request( [
            'season' => '2026',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'season', $result->get_error_codes() );
        $this->assertContains( 'Season not found', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_nonexistent_player_with_404(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $this->player_service->method( 'get_player' )->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '999',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactno', $result->get_error_codes() );
        $this->assertSame( 404, $result->get_error_data( 'contactno' )['status'] ?? null );
        $this->assertContains( 'Player 999 not found', $result->get_error_messages() );
    }

    public function test_tournament_entry_preserves_per_field_status_when_multiple_errors_accrue(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $this->player_service->method( 'get_player' )->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '999',
            'event'    => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactno', $result->get_error_codes() );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertSame( 404, $result->get_error_data( 'contactno' )['status'] ?? null );
        $this->assertSame( 400, $result->get_error_data( 'event' )['status'] ?? null );
    }

    public function test_tournament_entry_preserves_per_field_status_with_mixed_direct_push_and_404_errors(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->club_service->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club 888 not found' ) );
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $dto = $this->create_tournament_entry_request( [
            'contactno' => '',
            'clubId'    => '888',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactno', $result->get_error_codes() );
        $this->assertContains( 'clubId', $result->get_error_codes() );
        $this->assertSame( 400, $result->get_error_data( 'contactno' )['status'] ?? null );
        $this->assertSame( 404, $result->get_error_data( 'clubId' )['status'] ?? null );
        $this->assertContains( 'Telephone number required', $result->get_error_messages() );
        $this->assertContains( 'Club 888 not found', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_nonexistent_club_with_404(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->club_service->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club 888 not found' ) );

        $dto = $this->create_tournament_entry_request( [
            'clubId' => '888',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'clubId', $result->get_error_codes() );
        $this->assertSame( 404, $result->get_error_data( 'clubId' )['status'] ?? null );
        $this->assertContains( 'Club 888 not found', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_empty_phone_number_for_regular_user(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $dto = $this->create_tournament_entry_request( [
            'contactno' => '',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactno', $result->get_error_codes() );
        $this->assertContains( 'Telephone number required', $result->get_error_messages() );
    }

    public function test_tournament_entry_bypasses_phone_validation_for_admin(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 1 );

        $GLOBALS['wp_stubs_current_user_can'] = true;

        $dto = $this->create_tournament_entry_request( [
            'contactno' => '',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
    }

    public function test_tournament_entry_rejects_missing_email(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'contactemail' => '',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactemail', $result->get_error_codes() );
        $this->assertContains( 'Email address is required', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_invalid_email_format(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'contactemail' => 'invalid-email-format',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactemail', $result->get_error_codes() );
        $this->assertContains( 'Email address is required', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_email_already_used_by_another_player(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $this->validator_players['email']['taken@test.local'] = Player::from_array( [ 'ID' => 99, 'display_name' => 'Taken Player' ] );

        $dto = $this->create_tournament_entry_request( [
            'contactemail' => 'taken@test.local',
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'contactemail', $result->get_error_codes() );
        $this->assertContains( 'Email address already used by Taken Player', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_exceeding_max_entries(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $tournament->num_entries = 2;

        $event1 = $this->create_tournament_event( 10, 'MS' );
        $event2 = $this->create_tournament_event( 11, 'MD' );
        $event3 = $this->create_tournament_event( 12, 'XD' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event1, $event2, $event3 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap( [
            [ 10, $event1 ],
            [ 11, $event2 ],
            [ 12, $event3 ],
        ] );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'tournamentEvents' => '10,11,12',
            'event'            => [ '10', '11', '12' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertContains( 'You have entered too many events', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_empty_event_selection(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'event' => [],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertContains( 'You must select a event to enter', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_doubles_without_selected_partner(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'partnerId' => [ 10 => '' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner not selected', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_nonexistent_doubles_partner_with_404(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( function( $pid ) use ( $player ) {
            if ( (int) $pid === 10 ) {
                return $player;
            }
            throw new Player_Not_Found_Exception( 'Partner 999 not found' );
        } );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'partnerId' => [ 10 => '999' ],
            'partner'   => [ 10 => 'Unknown Partner' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertSame( 404, $result->get_error_data( 'partner-10' )['status'] ?? null );
        $this->assertContains( 'Partner 999 not found', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_partner_in_another_team_in_same_event(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );

        $partner_team = (object) [ 'team_id' => 101, 'league_team_id' => 501 ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturnCallback( fn( $pid ) => (int) $pid === 20 ? $partner_team : null );

        $dto = $this->create_tournament_entry_request( [
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner is in another team in this event', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_partner_too_old_in_junior_doubles(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'BD', 18, 0 ); // Boys Doubles U18
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 2008 ); // age 18 in 2026 (eligible)
        $partner = $this->create_tournament_player( 20, 'M', 2007 ); // age 19 in 2026 (too old)
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner is too old', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_partner_with_missing_birth_year_in_age_restricted_event(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'SD', 45, 0 ); // Senior Doubles 45+
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1975 ); // age 51 in 2026 (eligible)
        $partner = $this->create_tournament_player( 20, 'M', 0 ); // missing birth year
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner has no age specified', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_wrong_gender_partner_in_womens_doubles(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'WD' ); // Women Doubles
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'F', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1990 ); // Male partner in WD
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner is not eligible for this event', $result->get_error_messages() );
    }

    public function test_tournament_entry_rejects_wrong_gender_partner_in_mens_doubles(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' ); // Men Doubles
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'F', 1990 ); // Female partner in MD
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'partner-10', $result->get_error_codes() );
        $this->assertContains( 'Partner is not eligible for this event', $result->get_error_messages() );
    }

    public function test_notify_tournament_entry_open_passes_tournament_dates_view_model_to_player_and_club_templates(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $this->plugin->date_format = 'd/m/Y';
        $this->plugin->site_url = 'https://tennis.example.com';
        $this->plugin->method( 'get_confirmation_email' )->willReturn( 'tournaments@test.local' );

        $player = $this->create_tournament_player( 10, 'M', 1990, 'opted@test.local' );
        $this->tournament_repository->method( 'find_previous_tournament_players_with_optin' )->willReturn( [ $player ] );

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Club One' );
        $club->set_shortcode( 'C1' );
        $club->match_secretary = (object) [ 'display_name' => 'Sec One', 'email' => 'sec@test.local' ];
        $this->club_service->method( 'get_clubs_with_details' )->willReturn( [ $club ] );

        $captured_templates = [];
        $shortcodes = $this->createStub( \Racketmanager\Public\Shortcodes::class );
        $shortcodes->method( 'load_template' )->willReturnCallback( function( $tpl, $args ) use ( &$captured_templates ) {
            $captured_templates[] = [ 'tpl' => $tpl, 'args' => $args ];
            return 'email-body';
        } );
        $this->plugin->shortcodes = $shortcodes;

        $sent = $this->service->notify_tournament_entry_open( 1 );

        $this->assertSame( 2, $sent );
        $this->assertCount( 2, $captured_templates );
        $this->assertSame( 'tournament-entry-open', $captured_templates[0]['tpl'] );
        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Tournament\Tournament_Date_DTO::class, $captured_templates[0]['args']['tournament_dates'] );
        $this->assertStringContainsString( '30/08/2026', $captured_templates[0]['args']['tournament_dates']->date_display );
        $this->assertStringContainsString( 'https://tennis.example.com/entry-form/', $captured_templates[0]['args']['action_url'] );
        $this->assertSame( 'tournament-entry-open', $captured_templates[1]['tpl'] );
        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Tournament\Tournament_Date_DTO::class, $captured_templates[1]['args']['tournament_dates'] );
        $this->assertStringContainsString( '30/08/2026', $captured_templates[1]['args']['tournament_dates']->date_display );
        $this->assertStringContainsString( 'https://tennis.example.com/entry-form/', $captured_templates[1]['args']['action_url'] );
    }

    public function test_confirm_tournament_withdrawal_passes_tournament_dates_view_model_to_template(): void {
        $this->plugin->date_format = 'd/m/Y';
        $this->plugin->site_url    = 'https://tennis.example.com';
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $player = $this->create_tournament_player( 50, 'M', 1990, 'withdraw@test.local' );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );

        $entry_to_withdraw = $this->createMock( League_Team::class );
        $entry_to_withdraw->expects( $this->once() )->method( 'set_entered_state' )->with( Team_Profile::WITHDRAWN );
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->with( $entry_to_withdraw );

        $this->league_team_repository->method( 'find_player_teams_by_player_for_events' )->willReturn( [ $entry_to_withdraw ] );

        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 0.0 );

        $captured_template = null;
        $shortcodes = $this->createStub( \Racketmanager\Public\Shortcodes::class );
        $shortcodes->method( 'load_template' )->willReturnCallback( function( $tpl, $args ) use ( &$captured_template ) {
            $captured_template = [ 'tpl' => $tpl, 'args' => $args ];
            return 'withdrawal-body';
        } );
        $this->plugin->shortcodes = $shortcodes;

        $refund = $this->service->confirm_tournament_withdrawal( 1, 50 );

        $this->assertSame( 0, $refund );
        $this->assertNotNull( $captured_template );
        $this->assertSame( 'tournament-withdrawal', $captured_template['tpl'] );
        $this->assertInstanceOf( \Racketmanager\Domain\DTO\Tournament\Tournament_Date_DTO::class, $captured_template['args']['tournament_dates'] );
        $this->assertStringContainsString( '30/08/2026', $captured_template['args']['tournament_dates']->date_display );
        $this->assertStringContainsString( 'https://tennis.example.com/entry-form/', $captured_template['args']['action_url'] );
    }

    public function test_format_tournament_date_matches_legacy_presenter_semantics(): void {
        $method = new \ReflectionMethod( $this->service, 'format_tournament_date' );

        // Default plugin format is Y-m-d (as Tournament_Presenter fallback in HEAD).
        $this->assertSame( '2026-08-30', $method->invoke( $this->service, '2026-08-30', 'TBC' ) );

        $this->plugin->date_format = 'd/m/Y';
        $this->assertSame( '30/08/2026', $method->invoke( $this->service, '2026-08-30', 'TBC' ) );

        // Empty and the exact zero date use the fallback.
        $this->assertSame( 'TBC', $method->invoke( $this->service, null, 'TBC' ) );
        $this->assertSame( 'TBC', $method->invoke( $this->service, '', 'TBC' ) );
        $this->assertSame( 'N/A', $method->invoke( $this->service, '0000-00-00', 'N/A' ) );

        // A failed mysql2date() (unparseable) uses the fallback.
        $this->assertSame( 'N/A', $method->invoke( $this->service, 'not-a-date', 'N/A' ) );
    }

    public function test_format_date_matches_legacy_email_date_formatter_semantics(): void {
        $method = new \ReflectionMethod( $this->service, 'format_date' );

        $this->assertSame( '', $method->invoke( $this->service, null ) );
        $this->assertSame( '', $method->invoke( $this->service, '' ) );
        $this->assertSame( '', $method->invoke( $this->service, '0000-00-00' ) );
        $this->assertSame( '', $method->invoke( $this->service, '0000-00-00 00:00:00' ) );
        $this->assertSame( '', $method->invoke( $this->service, 'not-a-date' ) );
    }

    public function test_tournament_entry_singles_creates_new_team_when_no_existing_team(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10 ] )->willReturn( null );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $saved_team = null;
        $saved_player_ids = null;
        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->willReturnCallback( function( $team, $p_ids ) use ( &$saved_team, &$saved_player_ids ) {
            $saved_team       = $team;
            $saved_player_ids = $p_ids;
            return 501;
        } );

        $saved_league_team = null;
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->willReturnCallback( function( $lt ) use ( &$saved_league_team ) {
            if ( $lt instanceof League_Team ) {
                $saved_league_team = $lt;
            }
            return 1;
        } );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '10',
            'event'    => [ 10 ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( Team::class, $saved_team );
        $this->assertSame( 'Player 10', $saved_team->get_name() );
        $this->assertSame( 'MS', $saved_team->get_type() );
        $this->assertSame( 'P', $saved_team->get_team_type() );
        $this->assertSame( [ 10 ], $saved_player_ids );
        $this->assertSame( [ 10 ], $saved_player_ids );

        $this->assertInstanceOf( League_Team::class, $saved_league_team );
        $this->assertSame( 501, $saved_league_team->get_team_id() );
        $this->assertSame( 10, $saved_league_team->get_captain() );
        $this->assertSame( '2026', $saved_league_team->get_season() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_team->get_profile_enum() );
    }

    public function test_tournament_entry_singles_reuses_existing_team_from_repository(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10 ] )->willReturn( 301 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );

        $saved_league_team = null;
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->willReturnCallback( function( $lt ) use ( &$saved_league_team ) {
            if ( $lt instanceof League_Team ) {
                $saved_league_team = $lt;
            }
            return 1;
        } );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '10',
            'event'    => [ 10 ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( League_Team::class, $saved_league_team );
        $this->assertSame( 301, $saved_league_team->get_team_id() );
        $this->assertSame( 10, $saved_league_team->get_captain() );
        $this->assertSame( '2026', $saved_league_team->get_season() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_team->get_profile_enum() );
    }

    public function test_tournament_entry_singles_resubmitting_retains_league_team_without_creating_teams(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $existing_team = (object) [ 'team_id' => 300, 'league_team_id' => 900 ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( $existing_team );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->team_repository->expects( $this->never() )->method( 'find_team_by_players' );
        $this->league_team_repository->expects( $this->never() )->method( 'delete' );
        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );
        $this->league_team_repository->expects( $this->never() )->method( 'save' );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '10',
            'event'    => [ 10 ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 3, $result->status );
    }

    public function test_tournament_entry_doubles_reuses_existing_team_from_repository(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10, 20 ] )->willReturn( 401 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );

        $saved_league_team = null;
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->willReturnCallback( function( $lt ) use ( &$saved_league_team ) {
            if ( $lt instanceof League_Team ) {
                $saved_league_team = $lt;
            }
            return 1;
        } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'event'     => [ 10 ],
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( League_Team::class, $saved_league_team );
        $this->assertSame( 401, $saved_league_team->get_team_id() );
        $this->assertSame( 10, $saved_league_team->get_captain() );
        $this->assertSame( '2026', $saved_league_team->get_season() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_team->get_profile_enum() );
    }

    public function test_tournament_entry_partner_switch_deletes_old_league_team_and_creates_new_team(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $new_partner = $this->create_tournament_player( 30, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 30 ? $new_partner : null ) );

        $old_player_team = (object) [ 'team_id' => 200, 'league_team_id' => 800 ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $old_player_team : null );

        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10, 30 ] )->willReturn( null );

        $saved_team = null;
        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->with( $this->isInstanceOf( Team::class ), [ 10, 30 ] )->willReturnCallback( function( $team ) use ( &$saved_team ) {
            $saved_team = $team;
            return 201;
        } );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->league_team_repository->expects( $this->once() )->method( 'delete' )->with( 800 )->willReturn( true );

        $saved_league_team = null;
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->willReturnCallback( function( $lt ) use ( &$saved_league_team ) {
            if ( $lt instanceof League_Team ) {
                $saved_league_team = $lt;
            }
            return 1;
        } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'event'     => [ 10 ],
            'partnerId' => [ 10 => '30' ],
            'partner'   => [ 10 => 'Partner 30' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( Team::class, $saved_team );
        $this->assertSame( 'Player 10 / Player 30', $saved_team->get_name() );

        $this->assertInstanceOf( League_Team::class, $saved_league_team );
        $this->assertSame( 201, $saved_league_team->get_team_id() );
        $this->assertSame( 10, $saved_league_team->get_captain() );
        $this->assertSame( '2026', $saved_league_team->get_season() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_team->get_profile_enum() );
    }

    public function test_tournament_entry_partner_switch_reuses_existing_team_for_new_partner(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $new_partner = $this->create_tournament_player( 30, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 30 ? $new_partner : null ) );

        $old_player_team = (object) [ 'team_id' => 200, 'league_team_id' => 800 ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $old_player_team : null );

        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10, 30 ] )->willReturn( 450 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->league_team_repository->expects( $this->once() )->method( 'delete' )->with( 800 )->willReturn( true );
        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );

        $saved_league_team = null;
        $this->league_team_repository->expects( $this->once() )->method( 'save' )->willReturnCallback( function( $lt ) use ( &$saved_league_team ) {
            if ( $lt instanceof League_Team ) {
                $saved_league_team = $lt;
            }
            return 1;
        } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'event'     => [ 10 ],
            'partnerId' => [ 10 => '30' ],
            'partner'   => [ 10 => 'Partner 30' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( League_Team::class, $saved_league_team );
        $this->assertSame( 450, $saved_league_team->get_team_id() );
        $this->assertSame( 10, $saved_league_team->get_captain() );
        $this->assertSame( '2026', $saved_league_team->get_season() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_team->get_profile_enum() );
    }

    public function test_tournament_entry_resubmitting_same_partner_retains_league_team_without_creating_teams(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );

        $existing_team = (object) [ 'team_id' => 200, 'league_team_id' => 800 ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( $existing_team );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->team_repository->expects( $this->never() )->method( 'find_team_by_players' );
        $this->league_team_repository->expects( $this->never() )->method( 'delete' );
        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );
        $this->league_team_repository->expects( $this->never() )->method( 'save' );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'event'     => [ 10 ],
            'partnerId' => [ 10 => '20' ],
            'partner'   => [ 10 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 3, $result->status );
    }

    public function test_tournament_entry_returns_error_and_does_not_save_league_team_when_team_creation_fails(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->expects( $this->once() )->method( 'find_team_by_players' )->with( [ 10 ] )->willReturn( null );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );

        // Team save fails, returning 0
        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->willReturn( false );
        $this->league_team_repository->expects( $this->never() )->method( 'save' );

        $dto = $this->create_tournament_entry_request( [
            'playerId' => '10',
            'event'    => [ 10 ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertContains( 'entry_error', $result->get_error_codes() );
        $this->assertSame( 500, $result->get_error_data( 'entry_error' )['status'] ?? null );
        $this->assertSame( 500, $result->get_error_data()['status'] ?? null );
    }

    public function test_tournament_entry_partner_switch_failure_does_not_delete_existing_league_team_or_save_new_league_team(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 20, 'MD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $new_partner = $this->create_tournament_player( 30, 'M', 1992 );
        $this->player_service->method( 'get_player' )->willReturnMap( [
            [ 10, $player ],
            [ 30, $new_partner ],
        ] );

        // Existing entry with partner 20 (team 200, league_team 800)
        $existing_league_team = (object) [
            'team_id'        => 200,
            'league_team_id' => 800,
            'player_id'      => 10,
        ];
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturnCallback(
            fn( $pid, $lid, $season ) => (int) $pid === 10 ? $existing_league_team : null
        );

        $this->team_repository->expects( $this->once() )
            ->method( 'find_team_by_players' )
            ->with( [ 10, 30 ] )
            ->willReturn( null );

        // Team save fails, returning 0
        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->willReturn( false );

        // Crucial assertions: old league team must NOT be deleted, no new league team saved, no financials/entry saved
        $this->league_team_repository->expects( $this->never() )->method( 'delete' );
        $this->league_team_repository->expects( $this->never() )->method( 'save' );
        $this->tournament_entry_repository->expects( $this->never() )->method( 'save' );
        $this->finance_service->expects( $this->never() )->method( 'add_player_invoice_for_tournament' );
        $this->player_service->expects( $this->never() )->method( 'handle_tournament_entry_personal_information' );

        $dto = $this->create_tournament_entry_request( [
            'playerId'   => '10',
            'event'      => [ 20 ],
            'partnerId'  => [ 20 => '30' ],
            'partner'    => [ 20 => 'New Partner' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertContains( 'entry_error', $result->get_error_codes() );
        $this->assertSame( 500, $result->get_error_data( 'entry_error' )['status'] ?? null );
        $this->assertSame( 500, $result->get_error_data()['status'] ?? null );
    }

    public function test_tournament_entry_multi_event_second_team_creation_failure_does_not_persist_first_event_or_withdrawals(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $event20 = $this->create_tournament_event( 20, 'MD' );
        $event30 = $this->create_tournament_event( 30, 'XD' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10, $event20, $event30 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap( [
            [ 10, $event10 ],
            [ 20, $event20 ],
            [ 30, $event30 ],
        ] );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1992 );
        $this->player_service->method( 'get_player' )->willReturnMap( [
            [ 10, $player ],
            [ 20, $partner ],
        ] );

        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->league_team_repository->method( 'find_player_teams_by_player_for_events' )->willReturn( [ $this->createMock(\Racketmanager\Domain\Competition\League_Team::class) ] );
        $this->player_service->expects( $this->never() )->method( 'handle_tournament_entry_personal_information' );

        $this->team_repository->method( 'find_team_by_players' )->willReturnMap( [
            [ [ 10 ], null ],
            [ [ 10, 20 ], null ],
        ] );

        // Event 10 succeeds creating team 501, Event 20 fails returning 0
        $this->team_repository->expects( $this->exactly( 2 ) )
            ->method( 'save_with_players' )
            ->willReturnOnConsecutiveCalls( 501, false );

        // Crucial assertions: NO league team saves (not for event 10, not for missed event 30 withdrawal)
        $this->league_team_repository->expects( $this->never() )->method( 'save' );
        $this->league_team_repository->expects( $this->never() )->method( 'delete' );
        $this->tournament_entry_repository->expects( $this->never() )->method( 'save' );
        $this->finance_service->expects( $this->never() )->method( 'add_player_invoice_for_tournament' );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10,20,30',
            'event'            => [ 10, 20 ],
            'partnerId'        => [ 20 => '20' ],
            'partner'          => [ 20 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertContains( 'entry_error', $result->get_error_codes() );
        $this->assertSame( 500, $result->get_error_data( 'entry_error' )['status'] ?? null );
        $this->assertSame( 500, $result->get_error_data()['status'] ?? null );
    }

    public function test_tournament_entry_multi_event_success_with_singles_and_partner_switch(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $event10->primary_league = 100;
        $event20 = $this->create_tournament_event( 20, 'MD' );
        $event20->primary_league = 200;

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10, $event20 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap( [
            [ 10, $event10 ],
            [ 20, $event20 ],
        ] );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $new_partner = $this->create_tournament_player( 30, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturnMap( [
            [ 10, $player ],
            [ 30, $new_partner ],
        ] );

        $old_player_team = (object) [ 'team_id' => 200, 'league_team_id' => 800 ];
        $this->league_team_repository->method( 'player_already_entered_league' )
            ->willReturnCallback( function( $pid, $lid, $season ) use ( $old_player_team ) {
                if ( (int) $pid === 10 && (int) $lid === 200 ) {
                    return $old_player_team;
                }
                return null;
            } );

        // Preflight: event 10 (MS) creates new team 501; event 20 (MD) reuses existing team 450
        $this->team_repository->method( 'find_team_by_players' )->willReturnMap( [
            [ [ 10 ], null ],
            [ [ 10, 30 ], 450 ],
        ] );
        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->with( $this->isInstanceOf( Team::class ), [ 10 ] )->willReturn( 501 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 25.0 );

        $operations = [];
        $saved_league_teams = [];

        $this->league_team_repository->expects( $this->once() )
            ->method( 'delete' )
            ->with( 800 )
            ->willReturnCallback( function( $id ) use ( &$operations ) {
                $operations[] = [
                    'type' => 'delete',
                    'id'   => $id,
                ];
                return true;
            } );

        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->willReturnCallback( function( $lt ) use ( &$operations, &$saved_league_teams ) {
                if ( $lt instanceof League_Team ) {
                    $saved_league_teams[] = $lt;
                    $operations[] = [
                        'type'      => 'save_league_team',
                        'team_id'   => $lt->get_team_id(),
                        'league_id' => $lt->get_league_id(),
                    ];
                }
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'  => '10',
            'event'     => [ 10, 20 ],
            'partnerId' => [ 20 => '30' ],
            'partner'   => [ 20 => 'Partner 30' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );

        // Verify league team saves
        $this->assertCount( 2, $saved_league_teams );
        $this->assertSame( 501, $saved_league_teams[0]->get_team_id() );
        $this->assertSame( 100, $saved_league_teams[0]->get_league_id() );
        $this->assertSame( '2026', $saved_league_teams[0]->get_season() );
        $this->assertSame( 10, $saved_league_teams[0]->get_captain() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_teams[0]->get_profile_enum() );

        $this->assertSame( 450, $saved_league_teams[1]->get_team_id() );
        $this->assertSame( 200, $saved_league_teams[1]->get_league_id() );
        $this->assertSame( '2026', $saved_league_teams[1]->get_season() );
        $this->assertSame( 10, $saved_league_teams[1]->get_captain() );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_teams[1]->get_profile_enum() );

        // Verify delete(800) occurred before the second league team save
        $this->assertCount( 3, $operations );
        $this->assertSame( [
            'type'      => 'save_league_team',
            'team_id'   => 501,
            'league_id' => 100,
        ], $operations[0] );
        $this->assertSame( [
            'type' => 'delete',
            'id'   => 800,
        ], $operations[1] );
        $this->assertSame( [
            'type'      => 'save_league_team',
            'team_id'   => 450,
            'league_id' => 200,
        ], $operations[2] );
    }

    public function test_add_team_with_players_builds_team_and_delegates_to_save_with_players(): void {
        $player1 = $this->create_tournament_player( 10, 'M', 1990 );
        $player2 = $this->create_tournament_player( 20, 'M', 1992 );

        $saved_team = null;
        $this->team_repository->expects( $this->once() )
            ->method( 'save_with_players' )
            ->with( $this->isInstanceOf( Team::class ), [ 10, 20 ] )
            ->willReturnCallback( function( Team $team ) use ( &$saved_team ) {
                $saved_team = $team;
                return 777;
            } );
        $this->team_repository->expects( $this->never() )->method( 'save' );
        $this->team_repository->expects( $this->never() )->method( 'save_team_players' );

        $result = $this->service->add_team_with_players( [ $player1, $player2 ], 'MD' );

        $this->assertSame( 777, $result );
        $this->assertInstanceOf( Team::class, $saved_team );
        $this->assertNull( $saved_team->get_id() );
        $this->assertSame( 'Player 10 / Player 20', $saved_team->get_name() );
        $this->assertSame( 'MD', $saved_team->get_type() );
        $this->assertSame( 'P', $saved_team->get_team_type() );
    }

    public function test_add_team_with_players_single_player_uses_player_name_as_team_name(): void {
        $player = $this->create_tournament_player( 10, 'M', 1990 );

        $this->team_repository->expects( $this->once() )
            ->method( 'save_with_players' )
            ->with(
                $this->callback( fn( Team $team ) => 'Player 10' === $team->get_name() && 'MS' === $team->get_type() && 'P' === $team->get_team_type() ),
                [ 10 ]
            )
            ->willReturn( 501 );

        $this->assertSame( 501, $this->service->add_team_with_players( [ $player ], 'MS' ) );
    }

    public function test_add_team_with_players_returns_false_when_repository_rolls_back(): void {
        $player = $this->create_tournament_player( 10, 'M', 1990 );

        $this->team_repository->expects( $this->once() )->method( 'save_with_players' )->willReturn( false );

        $this->assertFalse( $this->service->add_team_with_players( [ $player ], 'MS' ) );
    }

    public function test_tournament_entry_partial_payment_calculates_remaining_balance_and_assigns_fee_and_invoice_dto(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 10;

        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $event20 = $this->create_tournament_event( 20, 'OS', null, 0 );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10, $event20 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap( [
            [ 10, $event10 ],
            [ 20, $event20 ],
        ] );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        // Fees: Event fee = 10.0, Competition fee = 5.0. 2 events entered => fee = 25.0
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        // Player previously paid 10.0 -> remaining balance due is 15.0
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 10.0 );

        /** @var Tournament_Invoice_Details_DTO|null $captured_invoice */
        $captured_invoice = null;
        $this->finance_service->expects( $this->once() )
            ->method( 'cancel_player_invoices_by_tournament' )
            ->with( 10, $tournament );
        $this->finance_service->expects( $this->once() )
            ->method( 'add_player_invoice_for_tournament' )
            ->with( $tournament, $this->callback( function( $invoice ) use ( &$captured_invoice ) {
                $captured_invoice = $invoice;
                return true;
            } ) );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( null );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10,20',
            'event'            => [ '10', '20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 1, $result->status );
        $this->assertTrue( $result->payment_required );
        $this->assertSame( '/entry-form/summer-tournament-tournament/payment/', $result->return_link );
        $this->assertSame( 'Tournament entered and payment outstanding', $result->message );
        $this->assertSame( 'success', $result->message_type );

        // Invoice DTO assertions
        $this->assertInstanceOf( Tournament_Invoice_Details_DTO::class, $captured_invoice );
        $this->assertSame( 10, $captured_invoice->id );
        $this->assertSame( 'Player 10', $captured_invoice->name );
        $this->assertSame( 2, $captured_invoice->num_teams );
        $this->assertSame( 20.0, $captured_invoice->fee_events );
        $this->assertSame( 5.0, $captured_invoice->fee_competition );
        $this->assertSame( 25.0, $captured_invoice->fee );
        $this->assertSame( 10.0, $captured_invoice->paid );
        $this->assertSame( 15.0, $captured_invoice->total );

        // Tournament_Entry assertions
        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 1, $captured_entry->get_tournament_id() );
        $this->assertSame( 10, $captured_entry->get_player_id() );
        $this->assertSame( 15, $captured_entry->get_fee() );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
    }

    public function test_tournament_entry_partial_payment_for_club_entry_assigns_status_2_and_invoice_dto(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 10;

        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        // Fee: Event fee = 10.0, Competition fee = 5.0 => total fee = 15.0
        // Paid = 5.0 => remaining balance due = 10.0
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 5.0 );

        /** @var Tournament_Invoice_Details_DTO|null $captured_invoice */
        $captured_invoice = null;
        $this->finance_service->expects( $this->once() )
            ->method( 'cancel_player_invoices_by_tournament' )
            ->with( 10, $tournament );
        $this->finance_service->expects( $this->once() )
            ->method( 'add_player_invoice_for_tournament' )
            ->with( $tournament, $this->callback( function( $invoice ) use ( &$captured_invoice ) {
                $captured_invoice = $invoice;
                return true;
            } ) );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( null );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'clubId'           => '1',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 1, $result->status );
        $this->assertTrue( $result->payment_required );
        $this->assertSame( '/entry-form/summer-tournament-tournament/payment/', $result->return_link );
        $this->assertSame( 'Tournament entered and payment outstanding', $result->message );
        $this->assertSame( 'success', $result->message_type );

        $this->assertInstanceOf( Tournament_Invoice_Details_DTO::class, $captured_invoice );
        $this->assertSame( 10.0, $captured_invoice->total );

        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
        $this->assertSame( 10, $captured_entry->get_fee() );
    }

    public function test_tournament_entry_partial_payment_by_other_user_sets_status_4(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 999; // User submitting is not player 10

        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        // Fee = 15.0, paid = 5.0 -> balance due = 10.0
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 5.0 );

        $this->finance_service->expects( $this->once() )->method( 'cancel_player_invoices_by_tournament' );
        $this->finance_service->expects( $this->once() )->method( 'add_player_invoice_for_tournament' );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 4, $result->status );
        $this->assertFalse( $result->payment_required );
        $this->assertNull( $result->return_link );
        $this->assertSame( 'Tournament entered and payment outstanding for player', $result->message );
        $this->assertSame( 'success', $result->message_type );
    }

    public function test_tournament_entry_full_payment_sets_zero_balance_cancels_invoices_and_no_new_invoice(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        // Fee = 15.0, paid = 15.0 -> balance due = 0.0
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        $this->finance_service->expects( $this->once() )
            ->method( 'cancel_player_invoices_by_tournament' )
            ->with( 10, $tournament );
        $this->finance_service->expects( $this->never() )
            ->method( 'add_player_invoice_for_tournament' );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( null );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 0, $result->status );
        $this->assertFalse( $result->payment_required );
        $this->assertNull( $result->return_link );
        $this->assertSame( 'Tournament entry complete', $result->message );
        $this->assertSame( 'success', $result->message_type );

        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 0, $captured_entry->get_fee() );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
    }

    public function test_tournament_entry_assigns_status_2_when_club_id_provided(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( null );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'clubId'           => '1',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
        $this->assertSame( 0, $captured_entry->get_fee() );
    }

    public function test_tournament_entry_rejects_missing_or_empty_club_with_400(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 10, 'MS' );
        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto_empty = $this->create_tournament_entry_request( [
            'clubId' => '',
        ] );

        $result_empty = $this->service->request_tournament_entry( $dto_empty );

        $this->assertInstanceOf( WP_Error::class, $result_empty );
        $this->assertContains( 'clubId', $result_empty->get_error_codes() );
        $this->assertSame( 400, $result_empty->get_error_data( 'clubId' )['status'] ?? null );
        $this->assertContains( 'Select the club you are a member of', $result_empty->get_error_messages() );

        $dto_zero = $this->create_tournament_entry_request( [
            'clubId' => '0',
        ] );

        $result_zero = $this->service->request_tournament_entry( $dto_zero );

        $this->assertInstanceOf( WP_Error::class, $result_zero );
        $this->assertContains( 'clubId', $result_zero->get_error_codes() );
        $this->assertSame( 400, $result_zero->get_error_data( 'clubId' )['status'] ?? null );
        $this->assertContains( 'Select the club you are a member of', $result_zero->get_error_messages() );
    }

    public function test_tournament_entry_updates_existing_entry_status_and_fee_for_club(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 5.0 );

        $existing_entry = Tournament_Entry::from_object( (object) [
            'id'            => 123,
            'tournament_id' => 1,
            'player_id'     => 10,
            'status'        => 0,
            'fee'           => 0,
            'club_id'       => null,
        ] );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( $existing_entry );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'clubId'           => '1',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 123, $captured_entry->get_id() );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
        $this->assertSame( 10, $captured_entry->get_fee() );
    }

    public function test_tournament_entry_marks_unselected_prior_events_as_withdrawn(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );
        $event20 = $this->create_tournament_event( 20, 'MD' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10, $event20 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturnMap( [
            [ 10, $event10 ],
            [ 20, $event20 ],
        ] );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15.0 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 100 );

        $prior_league_team = new League_Team();
        $prior_league_team->set_id( 333 );
        $prior_league_team->set_team_id( 50 );
        $prior_league_team->set_league_id( 1020 );
        $prior_league_team->set_season( 2026 );
        $prior_league_team->set_captain( 10 );
        $prior_league_team->set_entered_state( Team_Profile::ACTIVE );

        $this->league_team_repository->expects( $this->once() )
            ->method( 'find_player_teams_by_player_for_events' )
            ->with( 10, [ 20 ], '2026' )
            ->willReturn( [ $prior_league_team ] );

        $saved_league_teams = [];
        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->willReturnCallback( function( $lt ) use ( &$saved_league_teams ) {
                $saved_league_teams[] = $lt;
                return 1;
            } );

        $this->player_service->expects( $this->once() )
            ->method( 'save_opt_in' )
            ->with( 10, '1' );

        // Player enters only event 10, omitting event 20
        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10,20',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );

        // Verify that prior_league_team was transitioned to WITHDRAWN
        $this->assertSame( Team_Profile::WITHDRAWN, $prior_league_team->get_profile_enum() );

        // First save was the withdrawal, second save was the new league team for event 10
        $this->assertSame( $prior_league_team, $saved_league_teams[0] );
        $this->assertSame( Team_Profile::ACTIVE, $saved_league_teams[1]->get_profile_enum() );
        $this->assertSame( 100, $saved_league_teams[1]->get_team_id() );
        $this->assertSame( 1010, $saved_league_teams[1]->get_league_id() );
        $this->assertSame( '2026', $saved_league_teams[1]->get_season() );
        $this->assertSame( 10, $saved_league_teams[1]->get_captain() );
    }

    public function test_tournament_entry_updates_existing_entry_when_changing_club(): void {
        $GLOBALS['wp_stubs_get_current_user_id'] = 10;

        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 5.0 );

        $existing_entry = Tournament_Entry::from_object( (object) [
            'id'            => 123,
            'tournament_id' => 1,
            'player_id'     => 10,
            'status'        => 2,
            'fee'           => 50,
            'club_id'       => 5,
        ] );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( $existing_entry );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'clubId'           => '1',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 1, $result->status );

        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( 123, $captured_entry->id );
        $this->assertSame( 2, $captured_entry->get_status() );
        $this->assertSame( 1, $captured_entry->get_club_id() );
        $this->assertSame( 10, $captured_entry->get_fee() );
    }

    public function test_tournament_entry_overpayment_calculates_negative_balance_sets_status_2_refund_outstanding(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event10 = $this->create_tournament_event( 10, 'MS' );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event10 ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event10 );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        // Fees: Event fee = 10.0, Competition fee = 5.0 => total fee = 15.0
        // Paid total = 25.0 => total balance due = -10.0 (refund outstanding)
        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 5.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 25.0 );

        /** @var Tournament_Invoice_Details_DTO|null $captured_invoice */
        $captured_invoice = null;
        $this->finance_service->expects( $this->once() )
            ->method( 'cancel_player_invoices_by_tournament' )
            ->with( 10, $tournament );
        $this->finance_service->expects( $this->once() )
            ->method( 'add_player_invoice_for_tournament' )
            ->with( $tournament, $this->callback( function( $invoice ) use ( &$captured_invoice ) {
                $captured_invoice = $invoice;
                return true;
            } ) );

        /** @var Tournament_Entry|null $captured_entry */
        $captured_entry = null;
        $this->tournament_entry_repository->method( 'find_by_tournament_and_player' )->willReturn( null );
        $this->tournament_entry_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $entry ) use ( &$captured_entry ) {
                $captured_entry = $entry;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '10',
            'event'            => [ '10' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 2, $result->status );
        $this->assertFalse( $result->payment_required );
        $this->assertNull( $result->return_link );
        $this->assertSame( 'Tournament entry complete and refund outstanding', $result->message );
        $this->assertSame( 'warning', $result->message_type );

        $this->assertInstanceOf( Tournament_Invoice_Details_DTO::class, $captured_invoice );
        $this->assertSame( -10.0, $captured_invoice->total );

        $this->assertInstanceOf( Tournament_Entry::class, $captured_entry );
        $this->assertSame( -10, $captured_entry->get_fee() );
    }

    public function test_request_tournament_entry_first_time_singles_resolves_primary_league_from_fallback_and_saves_league_team(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        // Event with primary_league not set (0), exercising Event::get_primary_league_id() lazy query fallback
        $event = $this->create_tournament_event( 15, 'MS' );
        $event->primary_league = 0;
        $event->settings = [];
        $event->league_index = [];

        $this->event_repository->method( 'query_leagues' )
            ->willReturn( [ (object) [ 'id' => 2050 ] ] );

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );
        $this->team_repository->method( 'find_team_by_players' )->willReturn( 500 );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 0.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 10.0 );

        /** @var League_Team|null $captured_league_team */
        $captured_league_team = null;
        $this->league_team_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $lt ) use ( &$captured_league_team ) {
                $captured_league_team = $lt;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '15',
            'event'            => [ '15' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 0, $result->status );
        $this->assertSame( 'Tournament entry complete', $result->message );
        $this->assertInstanceOf( League_Team::class, $captured_league_team );
        $this->assertSame( 2050, $captured_league_team->get_league_id() );
        $this->assertSame( 500, $captured_league_team->get_team_id() );
        $this->assertSame( '2026', $captured_league_team->get_season() );
        $this->assertSame( 10, $captured_league_team->get_captain() );
        $this->assertSame( Team_Profile::ACTIVE, $captured_league_team->get_profile_enum() );
    }

    public function test_request_tournament_entry_adding_singles_to_existing_doubles_partner_entry_resolves_primary_league_and_saves_singles_team(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );

        // Singles event 15 has unset primary_league (0), falls back to repository query returning league 2050
        $singles_event = $this->create_tournament_event( 15, 'MS' );
        $singles_event->primary_league = 0;
        $singles_event->settings = [];
        $singles_event->league_index = [];

        // Doubles event 16 has primary_league 3050
        $doubles_event = $this->create_tournament_event( 16, 'MD' );
        $doubles_event->primary_league = 3050;
        $doubles_event->league_index = [ 3050 => '1' ];

        $this->event_repository->method( 'query_leagues' )
            ->willReturnCallback( function( $event_id ) {
                if ( (int) $event_id === 15 ) {
                    return [ (object) [ 'id' => 2050 ] ];
                }
                if ( (int) $event_id === 16 ) {
                    return [ (object) [ 'id' => 3050 ] ];
                }
                return [];
            } );

        $this->tournament_service->method( 'get_events_for_tournament' )
            ->willReturn( [ $singles_event, $doubles_event ] );
        $this->competition_service->method( 'get_event_by_id' )
            ->willReturnCallback( fn( $id ) => (int) $id === 15 ? $singles_event : ( (int) $id === 16 ? $doubles_event : null ) );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $partner = $this->create_tournament_player( 20, 'M', 1992 );
        $this->player_service->method( 'get_player' )
            ->willReturnCallback( fn( $pid ) => (int) $pid === 10 ? $player : ( (int) $pid === 20 ? $partner : null ) );

        // Player 10 and Partner 20 are already entered in doubles event 16 (league 3050, team 999, league_team 888)
        // Player 10 is NOT yet entered in singles event 15 (league 2050)
        $existing_doubles_team = (object) [ 'team_id' => 999, 'league_team_id' => 888 ];
        $this->league_team_repository->method( 'player_already_entered_league' )
            ->willReturnCallback( function( $pid, $lid, $season ) use ( $existing_doubles_team ) {
                if ( (int) $lid === 3050 && in_array( (int) $pid, [ 10, 20 ], true ) ) {
                    return $existing_doubles_team;
                }
                return null;
            } );

        $this->team_repository->method( 'find_team_by_players' )
            ->willReturnCallback( function( array $players ) {
                if ( count( $players ) === 1 && (int) $players[0] === 10 ) {
                    return 500; // existing single-player team
                }
                if ( count( $players ) === 2 ) {
                    return 999; // existing doubles team
                }
                return null;
            } );

        $this->tournament_service->method( 'get_fees' )->willReturn( (object) [ 'event' => 10.0, 'competition' => 0.0 ] );
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 20.0 );

        /** @var League_Team|null $captured_singles_league_team */
        $captured_singles_league_team = null;
        $this->league_team_repository->expects( $this->once() )
            ->method( 'save' )
            ->willReturnCallback( function( $lt ) use ( &$captured_singles_league_team ) {
                $captured_singles_league_team = $lt;
                return 1;
            } );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '15,16',
            'event'            => [ '15', '16' ],
            'partnerId'        => [ 16 => '20' ],
            'partner'          => [ 16 => 'Partner 20' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( Tournament_Entry_Response_DTO::class, $result );
        $this->assertSame( 0, $result->status );
        $this->assertSame( 'Tournament entry complete', $result->message );
        $this->assertSame( 'success', $result->message_type );

        $this->assertInstanceOf( League_Team::class, $captured_singles_league_team );
        $this->assertSame( 2050, $captured_singles_league_team->get_league_id() );
        $this->assertSame( 500, $captured_singles_league_team->get_team_id() );
        $this->assertSame( '2026', $captured_singles_league_team->get_season() );
        $this->assertSame( 10, $captured_singles_league_team->get_captain() );
        $this->assertSame( Team_Profile::ACTIVE, $captured_singles_league_team->get_profile_enum() );
    }

    public function test_request_tournament_entry_returns_error_when_event_has_no_primary_league(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        // Event with no primary league and query_leagues returning empty
        $event = $this->create_tournament_event( 16, 'MS' );
        $event->primary_league = 0;
        $event->settings = [];

        $this->event_query_service = $this->createMock( Event_Query_Service::class );
        $this->event_query_service->expects( $this->once() )
            ->method( 'get_primary_league_id' )
            ->with( $event )
            ->willReturn( null );
        $this->service = $this->create_competition_entry_service();

        $this->tournament_service->method( 'get_events_for_tournament' )->willReturn( [ $event ] );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $player = $this->create_tournament_player( 10, 'M', 1990 );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $dto = $this->create_tournament_entry_request( [
            'playerId'         => '10',
            'tournamentEvents' => '16',
            'event'            => [ '16' ],
        ] );

        $result = $this->service->request_tournament_entry( $dto );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event', $result->get_error_codes() );
        $this->assertContains( 'No league configured for event', $result->get_error_messages() );
    }

    public function test_validate_tournament_partner_resolves_primary_league_and_validates_partner(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 20, 'MD' );
        $event->primary_league = 0;
        $event->settings = [];

        $this->event_query_service = $this->createMock( Event_Query_Service::class );
        $this->event_query_service->expects( $this->once() )
            ->method( 'get_primary_league_id' )
            ->with( $event )
            ->willReturn( 3050 );
        $this->service = $this->create_competition_entry_service();

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $partner = $this->create_tournament_player( 20, 'M', 1992 );
        $this->player_service->method( 'get_player' )->willReturn( $partner );

        // Partner is already in a different team in league 3050
        $partner_team = new stdClass();
        $partner_team->team_id = 999;
        $this->league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'player_already_entered_league' )
            ->willReturnCallback( function( $pid, $lid, $season ) use ( $partner_team ) {
                $this->assertSame( 3050, $lid );
                $this->assertSame( 2026, $season );
                if ( $pid === 20 ) {
                    return $partner_team;
                }
                return null;
            } );

        $partner_request = new Tournament_Partner_Request_DTO( [
            'playerId'     => 10,
            'partnerId'    => 20,
            'tournamentId' => 1,
            'eventId'      => 20,
        ] );

        $result = $this->service->validate_tournament_partner( $partner_request, true );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'Partner is in another team in this event', $result->get_error_messages() );
    }

    public function test_validate_tournament_partner_returns_error_when_event_has_no_primary_league(): void {
        $tournament = $this->create_tournament( 1, '2026', '2026-08-30' );
        $event = $this->create_tournament_event( 21, 'MD' );
        $event->primary_league = 0;
        $event->settings = [];

        $this->event_repository->method( 'query_leagues' )
            ->willReturn( [] );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $partner = $this->create_tournament_player( 20, 'M', 1992 );
        $this->player_service->method( 'get_player' )->willReturn( $partner );

        $partner_request = new Tournament_Partner_Request_DTO( [
            'playerId'     => 10,
            'partnerId'    => 20,
            'tournamentId' => 1,
            'eventId'      => 21,
        ] );

        $result = $this->service->validate_tournament_partner( $partner_request, true );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'No league configured for event', $result->get_error_messages() );
    }

    public function test_request_cup_entry_returns_error_when_event_has_no_primary_league(): void {
        $competition = $this->create_competition();
        $competition->is_open = true;
        $this->competition_to_return = $competition;

        $event = $this->create_tournament_event( 30, 'MD' );
        $event->primary_league = 0;
        $event->settings = [];

        $this->event_repository->method( 'query_leagues' )
            ->willReturn( [] );

        $competition->events = [ 30 => $event ];
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $request = (object) [
            'competition_id' => 1,
            'season'         => '2026',
            'club_id'        => 5,
            'events_entered' => [
                30 => (object) [
                    'team_id'    => 100,
                    'captain_id' => 10,
                    'captain'    => 'John Doe',
                    'phone'      => '01234567890',
                    'email'      => 'john@test.local',
                    'match_day'  => 1,
                    'match_time' => '19:00',
                ],
            ],
            'acceptance'     => true,
            'comments'       => '',
        ];

        $team = new Team();
        $team->set_id( 100 );
        $team->set_name( 'Test Team' );
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );

        $result = $this->service->request_cup_entry( $request );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertContains( 'event-30', $result->get_error_codes() );
        $this->assertContains( 'No league configured for event', $result->get_error_messages() );
    }

    public function test_cup_entry_valid_throws_exception_when_no_league_configured_and_no_existing_teams(): void {
        $club_id = 1;
        $club = new Club();
        $club->set_id( $club_id );
        
        $club_entry = (object) [
            'season' => '2026',
            'events' => [
                (object) [
                    'id'   => 50,
                    'name' => 'Cup Event',
                    'team' => (object) [
                        'id'         => 100,
                        'league_id'  => 0,
                        'captain_id' => 10,
                        'captain'    => 'John Doe',
                        'telephone'  => '01234567890',
                        'email'      => 'john@test.local',
                        'match_day'  => 1,
                        'match_time' => '19:00',
                        'team_name'  => 'Test Team',
                    ],
                ],
            ],
        ];

        $this->club_repository->method( 'find_by_id' )->willReturn( $club );
        $this->league_team_repository->method( 'find_by_event_id' )->willReturn( [] );

        $this->expectException( League_Not_Found_Exception::class );
        $this->league_team_repository->expects( $this->never() )->method( 'save' );

        $this->service->cup_entry_valid( $club_id, $club_entry );
    }

    public function test_notify_team_entry_open_sends_email_with_formatted_dates_for_league_competition(): void {
        $option_gateway = new class implements Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'date_format' === $option ? 'd/m/Y' : $default;
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool { return true; }
            public function delete_option( string $option ): bool { return true; }
        };

        $captured_mails = [];
        $fake_mailer = new class( $captured_mails ) implements Mailer_Interface {
            public function __construct( private array &$captured_mails ) {}
            public function send( string|array|null $to, ?string $subject, ?string $message, string|array|null $headers = '', ?array $attachments = [] ): bool {
                $this->captured_mails[] = [
                    'to'      => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                ];
                return true;
            }
        };

        $this->event_query_service = $this->createMock( Event_Query_Service::class );
        $service = new Competition_Entry_Service(
            plugin_instance: $this->plugin,
            club_repository: $this->club_repository,
            league_repository: $this->createStub( League_Repository::class ),
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            tournament_repository: $this->tournament_repository,
            tournament_entry_repository: $this->tournament_entry_repository,
            club_service: $this->club_service,
            competition_service: $this->competition_service,
            finance_service: $this->finance_service,
            player_service: $this->player_service,
            tournament_service: $this->tournament_service,
            notify_service: $this->notify_service,
            event_query_service: $this->event_query_service,
            mailer: $fake_mailer,
            option_gateway: $option_gateway
        );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026', [
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => null,
            ] )
        );
        $this->competition_to_return = $competition;

        $this->competition_service->method( 'is_season_valid_for_competition' )
            ->willReturn( [
                'name'         => '2026',
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => null,
            ] );

        $event = $this->create_event( 10, 'MD' );
        $event->seasons = wp_json_encode( [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] );
        $this->event_query_service->expects( $this->once() )->method( 'get_constitution' )
            ->with( $event, [ 'season' => 2026, 'count' => true ] )
            ->willReturn( 1 );
        $this->competition_service->method( 'get_events_for_competition' )
            ->willReturn( [ $event ] );
        $this->competition_service->method( 'get_leagues_for_event' )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Leigh Tennis Club' );
        $club->set_shortcode( 'Leigh' );

        $this->plugin->method( 'get_clubs' )->willReturn( [ $club ] );
        $this->plugin->method( 'get_confirmation_email' )->willReturn( 'league@test.local' );
        $this->plugin->site_name = 'Test Tennis League';
        $this->plugin->site_url  = 'https://example.com';

        $secretary               = new Player();
        $secretary->set_id( 99 );
        $secretary->display_name = 'Alice Secretary';
        $secretary->email        = 'alice@test.local';

        $this->player_service->method( 'get_match_secretary_details' )
            ->willReturn( $secretary );

        $view_renderer      = $this->create_view_renderer();
        $shortcode_renderer = new Shortcode_Template_Renderer( $view_renderer );
        $shortcodes         = $this->createStub( \Racketmanager\Public\Shortcodes::class );
        $warnings           = [];
        $shortcodes->method( 'load_template' )->willReturnCallback(
            function( string $template, array $args, string $subdir = '' ) use ( $shortcode_renderer, &$warnings ): string {
                set_error_handler( static function ( int $errno, string $errstr, string $errfile, int $errline ) use ( &$warnings ): bool {
                    if ( ! ( error_reporting() & $errno ) ) {
                        return false;
                    }
                    $warnings[] = [ 'errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline ];
                    return true;
                } );
                try {
                    return $shortcode_renderer->render( $template, $args, $subdir );
                } finally {
                    restore_error_handler();
                }
            }
        );
        $this->plugin->shortcodes = $shortcodes;

        $result = $service->notify_team_entry_open( 1, 2026 );

        $this->assertFalse( property_exists( $result, 'error' ) && $result->error );
        $this->assertSame( '1 match secretaries notified', $result->msg );
        $this->assertCount( 1, $captured_mails );

        $mail = $captured_mails[0];
        $this->assertSame( 'Alice Secretary <alice@test.local>', $mail['to'] );
        $this->assertSame( 'Test Tennis League - Summer League 2026 Entry Open - Leigh Tennis Club', $mail['subject'] );
        $this->assertSame( [], $warnings, 'Rendering competition-entry-open template via service must produce zero warnings' );

        $body = $mail['message'];
        $this->assertStringContainsString( 'The closing date for entries is 15/04/2026.', $body );
        $this->assertStringContainsString( 'The competition will run from 01/05/2026 until 31/08/2026.', $body );
        $this->assertStringContainsString( 'https://example.com', $body );
        $this->assertStringContainsString( 'Test Tennis League', $body );
        $this->assertStringContainsString( 'https://example.com/entry-form/summer-league/2026/leigh/', $body );
        $this->assertStringNotContainsString( 'Finals day will held at', $body );
    }

    public function test_notify_team_entry_open_falls_back_to_default_date_format_when_option_is_empty(): void {
        $option_gateway = new class implements Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'date_format' === $option ? '' : $default;
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool { return true; }
            public function delete_option( string $option ): bool { return true; }
        };

        $captured_mails = [];
        $fake_mailer = new class( $captured_mails ) implements Mailer_Interface {
            public function __construct( private array &$captured_mails ) {}
            public function send( string|array|null $to, ?string $subject, ?string $message, string|array|null $headers = '', ?array $attachments = [] ): bool {
                $this->captured_mails[] = [
                    'to'      => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                ];
                return true;
            }
        };

        $this->event_query_service = $this->createMock( Event_Query_Service::class );
        $service = new Competition_Entry_Service(
            plugin_instance: $this->plugin,
            club_repository: $this->club_repository,
            league_repository: $this->createStub( League_Repository::class ),
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            tournament_repository: $this->tournament_repository,
            tournament_entry_repository: $this->tournament_entry_repository,
            club_service: $this->club_service,
            competition_service: $this->competition_service,
            finance_service: $this->finance_service,
            player_service: $this->player_service,
            tournament_service: $this->tournament_service,
            notify_service: $this->notify_service,
            event_query_service: $this->event_query_service,
            mailer: $fake_mailer,
            option_gateway: $option_gateway
        );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026', [
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => null,
            ] )
        );
        $this->competition_to_return = $competition;

        $this->competition_service->method( 'is_season_valid_for_competition' )
            ->willReturn( [
                'name'         => '2026',
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => null,
            ] );

        $event = $this->create_event( 10, 'MD' );
        $event->seasons = wp_json_encode( [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] );
        $this->event_query_service->expects( $this->once() )->method( 'get_constitution' )
            ->with( $event, [ 'season' => 2026, 'count' => true ] )
            ->willReturn( 1 );
        $this->competition_service->method( 'get_events_for_competition' )
            ->willReturn( [ $event ] );
        $this->competition_service->method( 'get_leagues_for_event' )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Leigh Tennis Club' );
        $club->set_shortcode( 'Leigh' );

        $this->plugin->method( 'get_clubs' )->willReturn( [ $club ] );
        $this->plugin->method( 'get_confirmation_email' )->willReturn( 'league@test.local' );
        $this->plugin->site_name = 'Test Tennis League';
        $this->plugin->site_url  = 'https://example.com';

        $secretary               = new Player();
        $secretary->set_id( 99 );
        $secretary->display_name = 'Alice Secretary';
        $secretary->email        = 'alice@test.local';

        $this->player_service->method( 'get_match_secretary_details' )
            ->willReturn( $secretary );

        $view_renderer      = $this->create_view_renderer();
        $shortcode_renderer = new Shortcode_Template_Renderer( $view_renderer );
        $shortcodes         = $this->createStub( \Racketmanager\Public\Shortcodes::class );
        $shortcodes->method( 'load_template' )->willReturnCallback(
            function( string $template, array $args, string $subdir = '' ) use ( $shortcode_renderer ): string {
                return $shortcode_renderer->render( $template, $args, $subdir );
            }
        );
        $this->plugin->shortcodes = $shortcodes;

        $result = $service->notify_team_entry_open( 1, 2026 );

        $this->assertFalse( property_exists( $result, 'error' ) && $result->error );
        $this->assertCount( 1, $captured_mails );

        $body = $captured_mails[0]['message'];
        $this->assertStringContainsString( 'The closing date for entries is 15/04/2026.', $body );
        $this->assertStringContainsString( 'The competition will run from 01/05/2026 until 31/08/2026.', $body );
    }

    public function test_notify_team_entry_open_sends_email_with_venue_for_cup_competition(): void {
        $option_gateway = new class implements Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'date_format' === $option ? 'd/m/Y' : $default;
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool { return true; }
            public function delete_option( string $option ): bool { return true; }
        };

        $captured_mails = [];
        $fake_mailer = new class( $captured_mails ) implements Mailer_Interface {
            public function __construct( private array &$captured_mails ) {}
            public function send( string|array|null $to, ?string $subject, ?string $message, string|array|null $headers = '', ?array $attachments = [] ): bool {
                $this->captured_mails[] = [
                    'to'      => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                ];
                return true;
            }
        };

        $venue_club = new Club();
        $venue_club->set_id( 10 );
        $venue_club->set_name( 'Westcliff Lawn Tennis Club' );
        $venue_club->set_shortcode( 'Westcliff' );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( $venue_club );

        $competition = Competition_Test_Factory::create(
            id: 2,
            name: 'Summer Cup',
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', [
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => 10,
            ] )
        );
        $this->competition_to_return = $competition;

        $this->competition_service->method( 'is_season_valid_for_competition' )
            ->willReturn( [
                'name'         => '2026',
                'date_closing' => '2026-04-15 00:00:00',
                'date_start'   => '2026-05-01 00:00:00',
                'date_end'     => '2026-08-31 00:00:00',
                'venue'        => 10,
            ] );

        $service = new Competition_Entry_Service(
            plugin_instance: $this->plugin,
            club_repository: $this->club_repository,
            league_repository: $this->createStub( League_Repository::class ),
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            tournament_repository: $this->tournament_repository,
            tournament_entry_repository: $this->tournament_entry_repository,
            club_service: $club_service,
            competition_service: $this->competition_service,
            finance_service: $this->finance_service,
            player_service: $this->player_service,
            tournament_service: $this->tournament_service,
            notify_service: $this->notify_service,
            event_query_service: $this->event_query_service,
            mailer: $fake_mailer,
            option_gateway: $option_gateway
        );

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Leigh Tennis Club' );
        $club->set_shortcode( 'Leigh' );

        $this->plugin->method( 'get_clubs' )->willReturn( [ $club ] );
        $this->plugin->method( 'get_confirmation_email' )->willReturn( 'cup@test.local' );
        $this->plugin->site_name = 'Test Tennis League';
        $this->plugin->site_url  = 'https://example.com';

        $secretary               = new Player();
        $secretary->set_id( 99 );
        $secretary->display_name = 'Alice Secretary';
        $secretary->email        = 'alice@test.local';

        $this->player_service->method( 'get_match_secretary_details' )
            ->willReturn( $secretary );

        $view_renderer      = $this->create_view_renderer();
        $shortcode_renderer = new Shortcode_Template_Renderer( $view_renderer );
        $shortcodes         = $this->createStub( \Racketmanager\Public\Shortcodes::class );
        $warnings           = [];
        $shortcodes->method( 'load_template' )->willReturnCallback(
            function( string $template, array $args, string $subdir = '' ) use ( $shortcode_renderer, &$warnings ): string {
                set_error_handler( static function ( int $errno, string $errstr, string $errfile, int $errline ) use ( &$warnings ): bool {
                    if ( ! ( error_reporting() & $errno ) ) {
                        return false;
                    }
                    $warnings[] = [ 'errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline ];
                    return true;
                } );
                try {
                    return $shortcode_renderer->render( $template, $args, $subdir );
                } finally {
                    restore_error_handler();
                }
            }
        );
        $this->plugin->shortcodes = $shortcodes;

        $result = $service->notify_team_entry_open( 2, 2026 );

        $this->assertFalse( property_exists( $result, 'error' ) && $result->error );
        $this->assertSame( '1 match secretaries notified', $result->msg );
        $this->assertCount( 1, $captured_mails );

        $mail = $captured_mails[0];
        $this->assertSame( 'Alice Secretary <alice@test.local>', $mail['to'] );
        $this->assertSame( 'Test Tennis League - Summer Cup 2026 Entry Open - Leigh Tennis Club', $mail['subject'] );
        $this->assertSame( [], $warnings, 'Rendering competition-entry-open template via service must produce zero warnings' );

        $body = $mail['message'];
        $this->assertStringContainsString( 'The closing date for entries is 15/04/2026.', $body );
        $this->assertStringContainsString( 'The competition will run from 01/05/2026 until 31/08/2026.', $body );
        $this->assertStringContainsString( 'Finals day will held at Westcliff.', $body );
        $this->assertStringContainsString( 'https://example.com', $body );
        $this->assertStringContainsString( 'Test Tennis League', $body );
        $this->assertStringContainsString( 'https://example.com/entry-form/summer-cup/2026/leigh/', $body );
    }
}
