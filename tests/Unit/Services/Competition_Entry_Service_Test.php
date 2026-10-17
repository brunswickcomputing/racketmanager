<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Team;
use Racketmanager\Domain\DTO\Competition\League_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use WP_Error;

#[AllowMockObjectsWithoutExpectations]
class Competition_Entry_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Competition_Entry_Service $service;
    private Competition_Service $competition_service;
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private League_Team_Repository $league_team_repository;
    private Player_Service|MockObject $player_service;
    private Notify_Service $notify_service;
    private Tournament_Repository $tournament_repository;
    private Tournament_Service $tournament_service;
    private Club_Service $club_service;
    private Finance_Service $finance_service;
    private RacketManager $plugin;
    private ?Club $club_to_return;

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
        $this->team_repository = $this->createStub( Team_Repository::class );
        $this->club_repository = $this->createStub( Club_Repository::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository::class );
        $this->player_service = $this->createMock( Player_Service::class );
        $this->notify_service = $this->createStub( Notify_Service::class );
        $this->tournament_repository = $this->createStub( Tournament_Repository::class );
        $this->tournament_service = $this->createStub( Tournament_Service::class );
        $this->club_service = $this->createStub( Club_Service::class );
        $this->finance_service = $this->createStub( Finance_Service::class );

        $container = new Simple_Container();
        $container->set( 'competition_service', $this->competition_service );
        $container->set( 'player_service', $this->player_service );
        $this->plugin->container = $container;
        $GLOBALS['racketmanager'] = $this->plugin;

        $club = new Club();
        $club->set_id( 1 );
        $club->set_name( 'Test Club' );
        $club->set_shortcode( 'TC' );
        $this->club_to_return = $club;
        $this->club_repository->method( 'find_by_id' )->willReturnCallback( fn() => $this->club_to_return );
        $this->club_service->method( 'get_club' )->willReturn( $club );

        $this->service = new Competition_Entry_Service(
            plugin_instance: $this->plugin,
            club_repository: $this->club_repository,
            league_repository: $this->createStub( League_Repository::class ),
            league_team_repository: $this->league_team_repository,
            team_repository: $this->team_repository,
            tournament_repository: $this->tournament_repository,
            tournament_entry_repository: $this->createStub( Tournament_Entry_Repository::class ),
            club_service: $this->club_service,
            competition_service: $this->competition_service,
            finance_service: $this->finance_service,
            player_service: $this->player_service,
            tournament_service: $this->tournament_service,
            notify_service: $this->notify_service
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
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
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
        $raw->match_days_allowed = [ '1' => '1', '2' => '1', '5' => '1', '6' => '1' ];
        $raw->seasons = '[]';
        $raw->competition_id = 1;
        return new Event( $raw );
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
        $data = array_replace_recursive( [
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
        $pending_league_team = new League_Team( $pending_team_row );

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
        $tournament = new Tournament( $raw );
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
        $raw->age_limit = $age_limit;
        $raw->age_offset = $age_offset;
        $raw->num_rubbers = 1;
        $raw->match_days_allowed = [ '1' => '1', '2' => '1' ];
        $raw->seasons = '[]';
        $raw->competition_id = 1;
        $raw->primary_league = 1000 + $id;
        $raw->league_index = [ ( 1000 + $id ) => '1' ];
        return new Event( $raw );
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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 25 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15 );

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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

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
        $this->finance_service->method( 'get_tournament_paid_total_for_player' )->willReturn( 15 );
        $this->league_team_repository->method( 'player_already_entered_league' )->willReturn( null );

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
}
