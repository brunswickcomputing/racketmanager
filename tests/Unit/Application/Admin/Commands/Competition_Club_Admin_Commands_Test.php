<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Admin\Commands;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Admin\Commands\Add_Club_Admin_Command;
use Racketmanager\Application\Admin\Commands\Add_Club_Player_Command;
use Racketmanager\Application\Admin\Commands\Add_Club_Role_Command;
use Racketmanager\Application\Admin\Commands\Add_Club_Team_Command;
use Racketmanager\Application\Admin\Commands\Add_Competition_Season_Command;
use Racketmanager\Application\Admin\Commands\Club_Handler;
use Racketmanager\Application\Admin\Commands\Club_Player_Handler;
use Racketmanager\Application\Admin\Commands\Club_Role_Handler;
use Racketmanager\Application\Admin\Commands\Club_Team_Handler;
use Racketmanager\Application\Admin\Commands\Competition_Handler;
use Racketmanager\Application\Admin\Commands\Competition_Season_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Club_Players_Command;
use Racketmanager\Application\Admin\Commands\Delete_Club_Roles_Command;
use Racketmanager\Application\Admin\Commands\Delete_Club_Teams_Command;
use Racketmanager\Application\Admin\Commands\Delete_Clubs_Command;
use Racketmanager\Application\Admin\Commands\Delete_Competition_Events_Command;
use Racketmanager\Application\Admin\Commands\Edit_Club_Admin_Command;
use Racketmanager\Application\Admin\Commands\Edit_Club_Team_Command;
use Racketmanager\Application\Admin\Commands\Edit_Competition_Season_Command;
use Racketmanager\Application\Admin\Commands\Schedule_Club_Player_Ratings_Command;
use Racketmanager\Application\Admin\Commands\Schedule_Global_Player_Ratings_Command;
use Racketmanager\Application\Admin\Commands\Update_Competition_Config_Command;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Club_Admin_Commands_Test extends TestCase {
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
     * @return array<string, array{string, array<mixed>}>
     */
    public static function invalid_commands_provider(): array {
        return [
            'delete_events_non_digit' => [
                Delete_Competition_Events_Command::class,
                [ [ 'abc' ] ],
            ],
            'delete_clubs_non_digit' => [
                Delete_Clubs_Command::class,
                [ [ 'xyz' ] ],
            ],
            'delete_club_players_non_digit' => [
                Delete_Club_Players_Command::class,
                [ [ 'bad' ], 1 ],
            ],
            'delete_club_roles_non_digit' => [
                Delete_Club_Roles_Command::class,
                [ [ 'invalid' ] ],
            ],
            'delete_club_teams_non_digit' => [
                Delete_Club_Teams_Command::class,
                [ [ 'error' ] ],
            ],
        ];
    }

    #[DataProvider( 'invalid_commands_provider' )]
    public function test_commands_reject_invalid_ids( string $class, array $arguments ): void {
        $this->expectException( InvalidArgumentException::class );
        self::assertInstanceOf( $class, new $class( ...$arguments ) );
    }

    public function test_update_competition_config_command_validates_and_handles(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $event_repository    = $this->createStub( Event_Repository_Interface::class );

        $config = (object) [ 'name' => 'Premier League' ];
        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->with( 10, $config )
            ->willReturn( 1 );

        $handler = new Competition_Handler( $competition_service, $event_repository );
        $command = new Update_Competition_Config_Command(
            competition_id: 10,
            competition_id_passed: 10,
            config: $config,
            form_data: [ 'competition_title' => 'Premier League' ]
        );

        $result = $handler->handle_update_competition_config( $command );
        self::assertSame( 'Competition updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_update_competition_config_command_mismatched_id(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $event_repository    = $this->createStub( Event_Repository_Interface::class );

        $handler = new Competition_Handler( $competition_service, $event_repository );
        $command = new Update_Competition_Config_Command(
            competition_id: 10,
            competition_id_passed: 20,
            config: new stdClass(),
            form_data: []
        );

        $result = $handler->handle_update_competition_config( $command );
        self::assertSame( 'Invalid competition id', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_competition_events_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $event_repository    = $this->createMock( Event_Repository_Interface::class );

        $event = $this->createStub( Event::class );
        $event->name = 'Men Singles';

        $event_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $event );

        $competition_service->expects( self::once() )
            ->method( 'delete_event' )
            ->with( $event );

        $handler = new Competition_Handler( $competition_service, $event_repository );
        $command = new Delete_Competition_Events_Command( event: [ '5' ] );

        $result = $handler->handle_delete_events( $command );
        self::assertSame( 'Men Singles has been deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'events', $result->tab_override );
    }

    public function test_club_handler_add_and_edit_club(): void {
        $GLOBALS['wp_stubs_players']['id'][10] = (object) [ 'id' => 10, 'display_name' => 'John Doe' ];

        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createStub( Player_Service::class );

        $club = new Club( new Club_Hydration_DTO( id: 1, name: 'Westcliff Tennis' ) );

        $club_service->expects( self::once() )
            ->method( 'add_club' )
            ->willReturn( $club );

        $club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $club_service->expects( self::once() )
            ->method( 'update_club' )
            ->willReturn( $club );

        $handler = new Club_Handler( $club_service, $player_service );

        $add_cmd = new Add_Club_Admin_Command(
            name: 'Westcliff Tennis',
            type: 'tennis',
            shortcode: 'WT',
            address: '123 Tennis Lane',
            contactno: null,
            website: null,
            founded: null,
            facilities: null,
            match_secretary_id: null,
            match_secretary_name: null,
            match_secretary_contactno: null,
            match_secretary_email: null,
        );
        $add_res = $handler->handle_add_club( $add_cmd );
        self::assertSame( 'Club added', $add_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $add_res->message_type );
        self::assertSame( 1, $add_res->entity_id );
        self::assertSame( [ 'view' => 'club', 'club_id' => 1 ], $add_res->query_params );

        $edit_cmd = new Edit_Club_Admin_Command(
            club_id: 1,
            name: 'Westcliff Tennis Club',
            type: 'tennis',
            shortcode: 'WT',
            address: '123 Tennis Lane',
            contactno: null,
            website: null,
            founded: null,
            facilities: null,
            match_secretary_id: 10,
            match_secretary_name: 'John Doe',
            match_secretary_contactno: '01234567890',
            match_secretary_email: 'john@example.com',
        );
        $edit_res = $handler->handle_edit_club( $edit_cmd );
        self::assertSame( 'Club updated', $edit_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $edit_res->message_type );
        self::assertSame( 1, $edit_res->entity_id );
        self::assertSame( [ 'view' => 'club', 'club_id' => 1 ], $edit_res->query_params );
    }

    public function test_club_handler_edit_club_validation_failure_and_redirect_params(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createStub( Player_Service::class );

        $club = new Club( new Club_Hydration_DTO( id: 1, name: 'Westcliff Tennis' ) );
        $club_service->expects( self::exactly( 2 ) )
            ->method( 'get_club' )
            ->with( 1 )
            ->willReturn( $club );

        $handler = new Club_Handler( $club_service, $player_service );

        // Missing match secretary
        $cmd_no_sec = new Edit_Club_Admin_Command(
            club_id: 1,
            name: 'Westcliff Tennis Club',
            type: 'tennis',
            shortcode: 'WT',
            address: '123 Tennis Lane',
            match_secretary_id: null,
            match_secretary_email: 'sec@example.com',
        );
        $res_no_sec = $handler->handle_edit_club( $cmd_no_sec );
        self::assertSame( Admin_Message_Type::ERROR, $res_no_sec->message_type );
        self::assertSame( 'Match secretary must be specified', $res_no_sec->message );
        self::assertSame( [ 'view' => 'club', 'club_id' => 1 ], $res_no_sec->query_params );

        // Missing email
        $GLOBALS['wp_stubs_players']['id'][10] = (object) [ 'id' => 10, 'display_name' => 'John Doe' ];
        $cmd_bad_email = new Edit_Club_Admin_Command(
            club_id: 1,
            name: 'Westcliff Tennis Club',
            type: 'tennis',
            shortcode: 'WT',
            address: '123 Tennis Lane',
            match_secretary_id: 10,
            match_secretary_contactno: '01234567890',
            match_secretary_email: '',
        );
        $res_bad_email = $handler->handle_edit_club( $cmd_bad_email );
        self::assertSame( Admin_Message_Type::ERROR, $res_bad_email->message_type );
        self::assertSame( 'Email address is required', $res_bad_email->message );
        self::assertSame( [ 'view' => 'club', 'club_id' => 1 ], $res_bad_email->query_params );
    }

    public function test_club_action_dispatcher_founded_mapping(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $captured_inputs = [];
        $club_service->expects( self::exactly( 2 ) )
            ->method( 'add_club' )
            ->willReturnCallback( function ( object $input ) use ( &$captured_inputs ) {
                $captured_inputs[] = $input;
                return new Club( new Club_Hydration_DTO( id: 1, name: 'Test' ) );
            } );

        $handler    = new Club_Handler( $club_service, $player_service );
        $dispatcher = new Club_Action_Dispatcher( $club_service, $player_service, $guard, $handler );

        // Empty founded string maps to null
        $dispatcher->handle( [], [
            'addClub' => 1,
            'club'    => 'Test Club',
            'type'    => 'tennis',
            'shortcode' => 'TC',
            'address' => '1 High St',
            'founded' => '',
        ] );
        self::assertNull( $captured_inputs[0]->founded );

        // Non-empty founded string maps to integer
        $dispatcher->handle( [], [
            'addClub' => 1,
            'club'    => 'Test Club 2',
            'type'    => 'tennis',
            'shortcode' => 'TC2',
            'address' => '2 High St',
            'founded' => '1995',
        ] );
        self::assertSame( 1995, $captured_inputs[1]->founded );
    }

    public function test_competition_action_dispatcher_do_action_event_bulk_no_op(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $event_repo   = $this->createStub( Event_Repository_Interface::class );
        $guard        = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Competition_Action_Dispatcher( $comp_service, $guard, $event_repo );

        $result = $dispatcher->handle( [], [
            'doActionEvent' => 1,
            'action'        => '-1',
        ] );

        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'No action specified', $result->message );
        self::assertSame( 'events', $result->tab_override );
    }

    public function test_competition_handler_delete_events_when_none_found(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $event_repo   = $this->createMock( Event_Repository_Interface::class );
        $event_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $handler = new Competition_Handler( $comp_service, $event_repo );
        $result  = $handler->handle_delete_events( new Delete_Competition_Events_Command( event: [ '999' ] ) );

        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'No events were found to delete', $result->message );
        self::assertSame( 'events', $result->tab_override );
    }

    public function test_competition_season_handler_add_season_schedules_expected_hooks(): void {
        $GLOBALS['wp_scheduled_events'] = [];

        $comp_service = $this->createMock( Competition_Service::class );
        $competition  = new Competition(
            name: 'League 10',
            type: 'league',
            age_group: 'open',
            seasons: [],
            settings: [],
            id: 10
        );
        $this->stub_competition_lookup( $comp_service, $competition );
        $comp_service->method( 'add_season' )->willReturn( true );

        $dispatcher = $this->create_season_dispatcher( $comp_service );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'addSeason'       => '1',
                    'competition_id'  => '10',
                    'homeAway'        => 'true',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Season added to competition', $result->message );

        $scheduled_hooks = array_column( $GLOBALS['wp_scheduled_events'], 'hook' );
        self::assertContains( 'rm_notify_team_entry_open', $scheduled_hooks );
        self::assertContains( 'rm_notify_team_entry_reminder', $scheduled_hooks );
        self::assertContains( 'rm_calculate_team_ratings', $scheduled_hooks );
        self::assertNotContains( 'rm_email_invitations', $scheduled_hooks );
        self::assertNotContains( 'rm_close_entries', $scheduled_hooks );
    }

    public function test_competition_season_handler_edit_cup_season_updates_round_length_and_home_away_diff(): void {
        $comp_service    = $this->createMock( Competition_Service::class );
        $season_year     = $this->test_season_year();
        $existing_season = $this->complete_season_record(
            [
                'name'           => $season_year,
                'round_length'   => 7,
                'home_away_diff' => 1,
                'venue'          => 1,
            ]
        );
        $competition = new Competition(
            name: 'Cup 20',
            type: 'cup',
            age_group: 'open',
            seasons: [ $season_year => $existing_season ],
            settings: [],
            id: 20
        );
        $this->stub_competition_lookup( $comp_service, $competition );

        $saved_season = null;
        $comp_service->expects( self::once() )
            ->method( 'update_season' )
            ->with(
                $competition,
                self::callback( function ( array $season ) use ( &$saved_season ) {
                    $saved_season = $season;
                    return 14 === $season['round_length'] && 2 === $season['home_away_diff'];
                } )
            )
            ->willReturn( true );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );

        $dispatcher = $this->create_season_dispatcher( $comp_service );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'editSeason'      => '1',
                    'competition_id'  => '20',
                    'season'          => $season_year,
                    'venue'           => '1',
                    'round_length'    => '14',
                    'home_away_diff'  => '2',
                    'homeAway'        => 'true',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Season updated', $result->message );
        self::assertNotNull( $saved_season );
        self::assertSame( 14, $saved_season['round_length'] );
        self::assertSame( 2, $saved_season['home_away_diff'] );
    }

    public function test_competition_season_handler_edit_league_preserves_custom_match_dates(): void {
        $comp_service    = $this->createMock( Competition_Service::class );
        $season_year     = $this->test_season_year();
        $dates           = $this->test_season_dates( $season_year );
        $custom_dates    = [ $dates['date_close'], 'custom' ];
        $existing_season = $this->complete_season_record(
            [
                'name'           => $season_year,
                'num_match_days' => 2,
                'round_length'   => 7,
                'match_dates'    => $custom_dates,
            ]
        );
        $competition = new Competition(
            name: 'League 30',
            type: 'league',
            age_group: 'open',
            seasons: [ $season_year => $existing_season ],
            settings: [],
            id: 30
        );
        $this->stub_competition_lookup( $comp_service, $competition );

        $saved_season = null;
        $comp_service->expects( self::once() )
            ->method( 'update_season' )
            ->with(
                $competition,
                self::callback( function ( array $season ) use ( &$saved_season ) {
                    $saved_season = $season;
                    return true;
                } )
            )
            ->willReturn( true );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );

        $dispatcher = $this->create_season_dispatcher( $comp_service );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'editSeason'      => '1',
                    'competition_id'  => '30',
                    'season'          => $season_year,
                    'num_match_days'  => '2',
                    'round_length'    => '14',
                    'homeAway'        => 'true',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Season updated', $result->message );
        self::assertSame( $custom_dates, $saved_season['match_dates'] );
    }

    public function test_competition_season_handler_add_season_skips_ratings_when_not_team_entry(): void {
        $GLOBALS['wp_scheduled_events'] = [];

        $comp_service = $this->createMock( Competition_Service::class );
        $competition  = new Competition(
            name: 'Tournament 40',
            type: 'tournament',
            age_group: 'open',
            seasons: [],
            settings: [],
            id: 40
        );
        $this->stub_competition_lookup( $comp_service, $competition );
        $comp_service->method( 'add_season' )->willReturn( true );

        $dispatcher = $this->create_season_dispatcher( $comp_service );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'addSeason'       => '1',
                    'competition_id'  => '40',
                    'venue'           => '1',
                    'homeAway'        => 'true',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $scheduled_hooks = array_column( $GLOBALS['wp_scheduled_events'], 'hook' );
        self::assertContains( 'rm_notify_team_entry_open', $scheduled_hooks );
        self::assertContains( 'rm_notify_team_entry_reminder', $scheduled_hooks );
        self::assertNotContains( 'rm_calculate_team_ratings', $scheduled_hooks );
    }

    public function test_competition_season_handler_edit_league_regenerates_match_dates_when_num_match_days_changes(): void {
        $comp_service    = $this->createMock( Competition_Service::class );
        $season_year     = $this->test_season_year();
        $dates           = $this->test_season_dates( $season_year );
        $existing_season = $this->complete_season_record(
            [
                'name'           => $season_year,
                'num_match_days' => 2,
                'round_length'   => 7,
                'home_away'      => false,
                'home_away_diff' => 0,
                'match_dates'    => [ $dates['date_close'], 'custom' ],
            ]
        );
        $competition = new Competition(
            name: 'League 50',
            type: 'league',
            age_group: 'open',
            seasons: [ $season_year => $existing_season ],
            settings: [],
            id: 50
        );
        $this->stub_competition_lookup( $comp_service, $competition );

        $saved_season = null;
        $comp_service->expects( self::once() )
            ->method( 'update_season' )
            ->with(
                $competition,
                self::callback( function ( array $season ) use ( &$saved_season ) {
                    $saved_season = $season;
                    return true;
                } )
            )
            ->willReturn( true );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );

        $dispatcher = $this->create_season_dispatcher( $comp_service );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'editSeason'      => '1',
                    'competition_id'  => '50',
                    'season'          => $season_year,
                    'num_match_days'  => '4',
                    'round_length'    => '7',
                    'home_away_diff'  => '0',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Season updated', $result->message );
        self::assertSame(
            [ $dates['match_day_1'], $dates['match_day_2'], $dates['match_day_3'], $dates['match_day_4'] ],
            $saved_season['match_dates']
        );
    }

    public function test_competition_season_handler_edit_season_propagates_to_valid_events_and_skips_empty_ids(): void {
        $comp_service = $this->createMock( Competition_Service::class );
        $event_repo   = $this->createMock( Event_Repository_Interface::class );
        $dates        = $this->test_season_dates();
        $season_year  = $dates['season'];

        $existing_season = $this->complete_season_record(
            [
                'name'           => $season_year,
                'num_match_days' => 2,
                'round_length'   => 7,
            ]
        );
        $competition = new Competition(
            name: 'League 60',
            type: 'league',
            age_group: 'open',
            seasons: [ $season_year => $existing_season ],
            settings: [],
            id: 60
        );
        $this->stub_competition_lookup( $comp_service, $competition );
        $comp_service->method( 'update_season' )->willReturn( true );

        // Event with valid ID 101
        $valid_event     = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $valid_event->id = 101;
        $valid_event->seasons = [ $season_year => $existing_season ];

        // Event with empty ID 0
        $empty_event     = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $empty_event->id = 0;
        $empty_event->seasons = [ $season_year => $existing_season ];

        $comp_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 60 )
            ->willReturn( [ $valid_event, $empty_event ] );

        // Expect update_seasons ONLY for valid event (101), not for empty event (0)
        $event_repo->expects( self::once() )
            ->method( 'update_seasons' )
            ->with(
                101,
                self::callback( function ( array $seasons ) use ( $season_year ) {
                    return isset( $seasons[ $season_year ] )
                        && true === $seasons[ $season_year ]['home_away']
                        && 2 === $seasons[ $season_year ]['num_match_days'];
                } )
            );

        $dispatcher = $this->create_season_dispatcher( $comp_service, $event_repo );
        $result     = $dispatcher->handle(
            [],
            $this->season_post(
                [
                    'editSeason'      => '1',
                    'competition_id'  => '60',
                    'season'          => $season_year,
                    'num_match_days'  => '2',
                    'round_length'    => '14',
                    'homeAway'        => 'true',
                    'fixedMatchDates' => 'true',
                ]
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Season updated', $result->message );
    }

    public function test_club_handler_delete_clubs(): void {
        $club_service   = $this->createMock( Club_Service::class );
        $player_service = $this->createStub( Player_Service::class );

        $club_service->expects( self::once() )
            ->method( 'remove_club' )
            ->with( 1 );

        $handler = new Club_Handler( $club_service, $player_service );
        $command = new Delete_Clubs_Command( club: [ '1' ] );

        $result = $handler->handle_delete_clubs( $command );
        self::assertSame( '1 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_club_player_handler_add_and_delete(): void {
        $registration_service = $this->createMock( Registration_Service::class );
        $player_service       = $this->createMock( Player_Service::class );

        $registration_service->expects( self::once() )
            ->method( 'register_player_to_club' )
            ->with( 2, 101 )
            ->willReturn( 'John Doe registered' );

        $registration_service->expects( self::once() )
            ->method( 'remove_registration' )
            ->with( 55, 101 );

        $handler = new Club_Player_Handler( $registration_service, $player_service );

        $add_cmd = new Add_Club_Player_Command(
            club_id: 2,
            user_id: 101
        );
        $add_res = $handler->handle_add_player( $add_cmd );
        self::assertSame( 'John Doe registered', $add_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $add_res->message_type );

        $del_cmd = new Delete_Club_Players_Command(
            clubPlayer: [ '55' ],
            user_id: 101
        );
        $del_res = $handler->handle_delete_club_players( $del_cmd );
        self::assertSame( '55 - Player has been removed from club', $del_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $del_res->message_type );
    }

    public function test_club_role_handler_add_and_delete(): void {
        $club_service = $this->createMock( Club_Service::class );

        $club_service->expects( self::once() )
            ->method( 'set_club_role' )
            ->with( 3, 5, 20 )
            ->willReturn( true );

        $club_service->expects( self::once() )
            ->method( 'remove_club_role' )
            ->with( 50 );

        $handler = new Club_Role_Handler( $club_service );

        $add_cmd = new Add_Club_Role_Command(
            club_id: 3,
            role_id: 5,
            user_id: 20
        );
        $add_res = $handler->handle_add_role( $add_cmd );
        self::assertSame( 'Role added', $add_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $add_res->message_type );

        $del_cmd = new Delete_Club_Roles_Command(
            role: [ '50' ]
        );
        $del_res = $handler->handle_delete_roles( $del_cmd );
        self::assertSame( 'Role deleted', $del_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $del_res->message_type );
    }

    public function test_club_team_handler_add_edit_and_delete(): void {
        $club_service = $this->createMock( Club_Service::class );
        $team_service = $this->createMock( Team_Service::class );

        $team = $this->createStub( Team::class );
        $team->method( 'get_name' )->willReturn( 'Team A' );

        $club_service->expects( self::once() )
            ->method( 'create_team' )
            ->with( 4, 'mens' )
            ->willReturn( $team );

        $team_service->expects( self::once() )
            ->method( 'amend_team_for_club' )
            ->willReturn( true );

        $team_service->expects( self::once() )
            ->method( 'get_team_by_id' )
            ->with( 12 )
            ->willReturn( $team );

        $team_service->expects( self::once() )
            ->method( 'delete_team' )
            ->with( 12 )
            ->willReturn( true );

        $handler = new Club_Team_Handler( $club_service, $team_service );

        $add_cmd = new Add_Club_Team_Command(
            club_id: 4,
            team_type: 'mens'
        );
        $add_res = $handler->handle_add_team( $add_cmd );
        self::assertSame( 'Team added', $add_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $add_res->message_type );

        $edit_cmd = new Edit_Club_Team_Command(
            team_id: '12',
            club_id: 4,
            team_type: 'mens'
        );
        $edit_res = $handler->handle_edit_team( $edit_cmd );
        self::assertSame( 'Team updated', $edit_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $edit_res->message_type );

        $del_cmd = new Delete_Club_Teams_Command(
            team: [ '12' ]
        );
        $del_res = $handler->handle_delete_teams( $del_cmd );
        self::assertSame( 'Team A deleted', $del_res->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $del_res->message_type );
    }

    public function test_container_resolves_all_admin_handlers_and_dispatchers(): void {
        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb_mock;

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->method( 'get_options' )->willReturn( [] );
        $app_mock->site_url = 'https://example.com';
        $app_mock->site_name = 'Test Site';

        $container = Container_Bootstrap::boot( $app_mock );

        self::assertTrue( $container->has( 'competition_handler' ) );
        self::assertTrue( $container->has( 'competition_season_handler' ) );
        self::assertTrue( $container->has( 'club_handler' ) );
        self::assertTrue( $container->has( 'club_player_handler' ) );
        self::assertTrue( $container->has( 'club_team_handler' ) );
        self::assertTrue( $container->has( 'club_role_handler' ) );

        self::assertInstanceOf( Competition_Handler::class, $container->get( 'competition_handler' ) );
        self::assertInstanceOf( Competition_Season_Handler::class, $container->get( 'competition_season_handler' ) );
        self::assertInstanceOf( Club_Handler::class, $container->get( 'club_handler' ) );
        self::assertInstanceOf( Club_Player_Handler::class, $container->get( 'club_player_handler' ) );
        self::assertInstanceOf( Club_Team_Handler::class, $container->get( 'club_team_handler' ) );
        self::assertInstanceOf( Club_Role_Handler::class, $container->get( 'club_role_handler' ) );

        self::assertInstanceOf( Competition_Action_Dispatcher::class, $container->get( 'competition_action_dispatcher' ) );
        self::assertInstanceOf( Competition_Season_Action_Dispatcher::class, $container->get( 'competition_season_action_dispatcher' ) );
        self::assertInstanceOf( Club_Action_Dispatcher::class, $container->get( 'club_action_dispatcher' ) );
        self::assertInstanceOf( Club_Player_Action_Dispatcher::class, $container->get( 'club_player_action_dispatcher' ) );
        self::assertInstanceOf( Club_Team_Action_Dispatcher::class, $container->get( 'club_team_action_dispatcher' ) );
        self::assertInstanceOf( Club_Role_Action_Dispatcher::class, $container->get( 'club_role_action_dispatcher' ) );
    }

    private function test_season_year(): string {
        return (string) ( (int) gmdate( 'Y' ) + 5 );
    }

    /**
     * @param string|null $season_year
     * @return array<string, string>
     */
    private function test_season_dates( ?string $season_year = null ): array {
        $year = $season_year ?? $this->test_season_year();

        return [
            'season'       => $year,
            'date_open'    => "{$year}-01-01",
            'date_close'   => "{$year}-02-01",
            'date_start'   => "{$year}-03-01",
            'date_end'     => "{$year}-04-01",
            'match_day_1'  => "{$year}-03-01",
            'match_day_2'  => "{$year}-03-08",
            'match_day_3'  => "{$year}-03-15",
            'match_day_4'  => "{$year}-03-22",
        ];
    }

    /**
     * POST-shaped season payload matching Competition_Season_Action_Dispatcher::get_season_input().
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function season_post( array $overrides = [] ): array {
        $dates = $this->test_season_dates();
        return array_merge(
            [
                'competition_id'    => '10',
                'season'            => $dates['season'],
                'grade'             => 'A',
                'dateOpen'          => $dates['date_open'],
                'dateClose'         => $dates['date_close'],
                'dateStart'         => $dates['date_start'],
                'dateEnd'           => $dates['date_end'],
                'venue'             => '1',
                'max_teams'         => '8',
                'teams_per_club'    => '2',
                'teams_prom_relg'   => '1',
                'lowest_promotion'  => '2',
                'num_match_days'    => '2',
                'round_length'      => '7',
                'home_away_diff'    => '0',
                'filler_weeks'      => '0',
                'fixedMatchDates'   => 'true',
                'homeAway'          => 'true',
                'competition_code'  => '',
            ],
            $overrides
        );
    }

    /**
     * Stored season array with every field get_season_input() always sets.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function complete_season_record( array $overrides = [] ): array {
        $dates = $this->test_season_dates();
        return array_merge(
            [
                'name'              => $dates['season'],
                'grade'             => 'A',
                'date_open'         => $dates['date_open'],
                'date_closing'      => $dates['date_close'],
                'date_start'        => $dates['date_start'],
                'date_end'          => $dates['date_end'],
                'venue'             => 1,
                'max_teams'         => 8,
                'teams_per_club'    => 2,
                'teams_prom_relg'   => 1,
                'lowest_promotion'  => 2,
                'num_match_days'    => 2,
                'round_length'      => 7,
                'home_away_diff'    => 0,
                'filler_weeks'      => 0,
                'fixed_match_dates' => true,
                'home_away'         => true,
                'competition_code'  => '',
                'fee_competition'   => null,
                'fee_event'         => null,
                'fee_lead_time'     => null,
                'fee_id'            => null,
                'match_dates'       => [ $dates['match_day_1'], $dates['match_day_2'] ],
            ],
            $overrides
        );
    }

    private function stub_competition_lookup( Competition_Service $comp_service, Competition $competition ): void {
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_competition' )->willReturn( $competition );
    }

    private function create_season_dispatcher( Competition_Service $comp_service, ?Event_Repository_Interface $event_repo = null ): Competition_Season_Action_Dispatcher {
        return new Competition_Season_Action_Dispatcher(
            $comp_service,
            $this->createStub( Finance_Service::class ),
            $this->createStub( Action_Guard_Interface::class ),
            $this->createStub( Competition_Season_Service::class ),
            $event_repo ?? $this->createStub( Event_Repository_Interface::class )
        );
    }
}
