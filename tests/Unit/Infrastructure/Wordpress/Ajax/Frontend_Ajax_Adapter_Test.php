<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Cup_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Competition\League_Entry_Request_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

#[AllowMockObjectsWithoutExpectations]
class Frontend_Ajax_Adapter_Test extends TestCase {
    private Stub|Security_Service_Interface $security_service;
    private Competition_Service|MockObject|Stub $competition_service;
    private Competition_Entry_Service|MockObject|Stub $competition_entry_service;
    private Team_Service|MockObject|Stub $team_service;
    private Player_Service|MockObject|Stub $player_service;
    private Registration_Service|MockObject|Stub $registration_service;
    private Club_Service|MockObject|Stub $club_service;
    private Tournament_Service|MockObject|Stub $tournament_service;
    private Fixture_Repository_Interface|MockObject|Stub $fixture_repository;
    private \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface|MockObject|Stub $rubber_repository;
    private Frontend_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service          = $this->createStub( Security_Service_Interface::class );
        $this->competition_service       = $this->createStub( Competition_Service::class );
        $this->competition_entry_service = $this->createStub( Competition_Entry_Service::class );
        $this->team_service              = $this->createStub( Team_Service::class );
        $this->player_service            = $this->createStub( Player_Service::class );
        $this->registration_service      = $this->createStub( Registration_Service::class );
        $this->club_service              = $this->createStub( Club_Service::class );
        $this->tournament_service        = $this->createStub( Tournament_Service::class );
        $this->fixture_repository        = $this->createStub( Fixture_Repository_Interface::class );
        $this->rubber_repository         = $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class );
        $this->adapter                   = $this->make_adapter();
        $_POST                           = [];
        $_GET                            = [];
        $_REQUEST                        = [];
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
    }

    protected function tearDown(): void {
        parent::tearDown();
        unset( $GLOBALS['wp_stubs_is_user_logged_in'], $GLOBALS['wp_stubs_teams'] );
    }

    private function make_adapter( ?\Racketmanager\Application\Player\Queries\Get_Player_Details_Handler $get_player_details_handler = null ): Frontend_Ajax_Adapter {
        $get_player_details_handler ??= new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler(
            $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class )
        );
        $response_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 200 )
        );
        $response_factory->method( 'create_error_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 400 )
        );
        $response_factory->method( 'create_raw_response' )->willReturnCallback(
            static fn( $content, $status = null ) => new Response( $content, $status ?? 200 )
        );

        $player_search_handler = $this->createStub( \Racketmanager\Application\General\Queries\Get_Player_Search_Handler::class );
        $alert_handler         = $this->createStub( \Racketmanager\Application\General\Queries\Get_Alert_Handler::class );
        $view_presenter        = $this->createStub( \Racketmanager\Presentation\Presenters\Shortcode_View_Presenter::class );
        $view_presenter->method( 'render' )->willReturnCallback( function ( \Racketmanager\Presentation\View_Models\Shortcode_View_Model $vm ) {
            if ( isset( $vm->vars['msg'] ) ) {
                return '<div class="alert alert-' . ( $vm->vars['class'] ?? 'danger' ) . '">' . $vm->vars['msg'] . '</div>';
            }
            return '<div>Player search for: ' . ( $vm->vars['players'] ?? '' ) . '</div>';
        } );
        $player_search_handler->method( 'handle' )->willReturnCallback( function ( \Racketmanager\Application\General\Queries\Get_Player_Search_Query $q ) {
            return \Racketmanager\Presentation\View_Models\Shortcode_View_Model::for_template( 'players-list', [ 'players' => $q->search ] );
        } );
        $alert_handler->method( 'handle' )->willReturnCallback( function ( \Racketmanager\Application\General\Queries\Get_Alert_Query $q ) {
            return \Racketmanager\Presentation\View_Models\Shortcode_View_Model::for_template( 'alert', [ 'msg' => $q->msg, 'class' => $q->type ] );
        } );
        $search_players_handler = new \Racketmanager\Application\Player\Queries\Search_Players_Handler(
            player_search_handler: $player_search_handler,
            alert_handler: $alert_handler,
            view_presenter: $view_presenter
        );

        return new Frontend_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $this->competition_service,
            $this->competition_entry_service,
            $this->team_service,
            $this->player_service,
            $this->registration_service,
            $this->club_service,
            $this->tournament_service,
            $this->fixture_repository,
            $this->rubber_repository,
            search_players_handler: $search_players_handler,
            get_player_details_handler: $get_player_details_handler
        );
    }

    private function create_player_dto( string|float|null $wtn_d = null ): \Racketmanager\Domain\DTO\Club\Club_Player_DTO {
        $player = $this->createStub( \Racketmanager\Domain\DTO\Club\Club_Player_DTO::class );
        $player->wtn = null !== $wtn_d ? [ 'D' => $wtn_d ] : [];
        return $player;
    }

    /**
     * @param array<int, \Racketmanager\Domain\DTO\Club\Club_Player_DTO> $players
     */
    private function create_registration_service_for_players( array $players ): Registration_Service {
        $reg_service = $this->createStub( Registration_Service::class );
        $reg_service->method( 'get_registration' )->willReturnCallback(
            static fn( int $id ) => $players[ $id ] ?? null
        );
        return $reg_service;
    }

    private function setup_event_for_team_order( int $event_id = 10, string $type = 'MD' ): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $event       = $this->createStub( Event::class );
        $event->type = $type;
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( $this->once() )->method( 'get_event_by_id' )->with( $event_id )->willReturn( $event );
        $this->competition_service = $competition_service;
    }

    private function create_rubber_with_recorder( int $rubber_number, ?array &$recorded_players, int $id = 1 ): \Racketmanager\Domain\Fixture\Rubber {
        $rubber = $this->createStub( \Racketmanager\Domain\Fixture\Rubber::class );
        $rubber->method( 'get_id' )->willReturn( $id );
        $rubber->rubber_number = $rubber_number;
        $rubber->method( 'set_players' )->willReturnCallback(
            static function ( array $players ) use ( &$recorded_players ): void {
                $recorded_players = $players;
            }
        );
        return $rubber;
    }

    private function setup_fixture_repo_for_match( int $match_id, ?object $match ): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( $match_id )
            ->willReturn( $match );
        $this->fixture_repository = $fixture_repo;
    }

    public function test_register_hooks_routes_all_frontend_actions(): void {
        $controller = new Frontend_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = [];
        $controller->register_hooks( $registry );

        $this->assertSame( [ $controller, 'update_team' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_update_team'][0] );
        $this->assertSame( [ $controller, 'logged_out' ], $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_update_team'][0] );
        $this->assertSame( [ $controller, 'update_player' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_update_player'][0] );
        $this->assertSame( [ $controller, 'logged_out' ], $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_update_player'][0] );
        $this->assertSame( [ $controller, 'cup_entry' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_cup_entry'][0] );
        $this->assertSame( [ $controller, 'league_entry' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_league_entry'][0] );
        $this->assertSame( [ $controller, 'search_players' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_search_players'][0] );
        $this->assertSame( [ $controller, 'search_players' ], $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_search_players'][0] );
        $this->assertSame( [ $controller, 'tab_data' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_tab_data'][0] );
        $this->assertSame( [ $controller, 'get_player_details' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_player_details'][0] );
    }

    public function test_logged_out_returns_401(): void {
        $response = $this->adapter->logged_out();
        $this->assertSame( 401, $response->get_status_code() );
    }

    public function test_logged_out_modal_returns_401_with_modal_alert(): void {
        $response = $this->adapter->logged_out_modal();
        $this->assertSame( 401, $response->get_status_code() );
    }

    public function test_search_players_returns_raw_string(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->search_players( [
            'security'      => 'valid',
            'search_string' => 'John',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
        $this->assertStringContainsString( 'Player search for: John', (string) $response->get_content() );
    }

    public function test_search_players_missing_string_returns_alert(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->search_players( [
            'security' => 'valid',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'Search string not supplied', (string) $response->get_content() );
    }

    public function test_search_players_nonce_failure_returns_200_html(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->search_players( [
            'security' => 'invalid',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'alert-danger', (string) $response->get_content() );
    }

    public function test_tab_data_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $comp = $this->createStub( Competition::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->competition_service->expects( $this->once() )->method( 'get_by_id' )->with( 1 )->willReturn( $comp );

        $this->adapter  = $this->make_adapter();
        $response       = $this->adapter->tab_data( [
            'security' => 'valid',
            'target'   => 'competition',
            'targetId' => 1,
            'tab'      => 'events',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_tab_data_nonce_failure_returns_200_plain_text(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->tab_data( [
            'security' => 'invalid',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
    }

    public function test_tab_data_invalid_target(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->tab_data( [
            'security' => 'valid',
            'target'   => 'unknown',
            'targetId' => 1,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_show_team_order_players_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->show_team_order_players( [
            'security' => 'valid',
            'teamId'   => 1,
            'eventId'  => 2,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'team-order-players', (string) $response->get_content() );
    }

    public function test_validate_team_order_missing_event_id_returns_404(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->validate_team_order( [
            'security' => 'valid',
        ] );
        $this->assertSame( 404, $response->get_status_code() );
        $validator = $response->get_content();
        $this->assertTrue( $validator->error );
        $this->assertContains( 'event_id', $validator->err_flds );
        $this->assertContains( 'Event id not supplied', $validator->err_msgs );
    }

    public function test_validate_team_order_event_not_found_returns_404(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( $this->once() )->method( 'get_event_by_id' )
            ->with( 999 )
            ->willThrowException( new \Racketmanager\Exceptions\Event_Not_Found_Exception( 'Event not found' ) );
        $this->competition_service = $competition_service;
        $this->adapter             = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security' => 'valid',
            'eventId'  => 999,
        ] );
        $this->assertSame( 404, $response->get_status_code() );
        $validator = $response->get_content();
        $this->assertTrue( $validator->error );
        $this->assertContains( 'event_id', $validator->err_flds );
        $this->assertContains( 'Event not found', $validator->err_msgs );
    }

    public function test_validate_team_order_unselected_player_returns_400(): void {
        $this->setup_event_for_team_order();
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1 ],
            'players'    => [ 1 => [ 'p1' => 0 ] ],
        ] );
        $this->assertSame( 400, $response->get_status_code() );
        $validator = $response->get_content();
        $this->assertTrue( $validator->error );
        $this->assertContains( 'players_1_p1', $validator->err_flds );
        $this->assertContains( 'Player not selected', $validator->err_msgs );
    }

    public function test_validate_team_order_registration_not_found_returns_400(): void {
        $this->setup_event_for_team_order();
        $reg_service = $this->createMock( Registration_Service::class );
        $reg_service->expects( $this->once() )->method( 'get_registration' )
            ->with( 404 )
            ->willThrowException( new \Racketmanager\Exceptions\Registration_Not_Found_Exception( 'Player registration not found' ) );

        $this->registration_service = $reg_service;
        $this->adapter              = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1 ],
            'players'    => [ 1 => [ 'p1' => 404 ] ],
        ] );
        $this->assertSame( 400, $response->get_status_code() );
        $validator = $response->get_content();
        $this->assertTrue( $validator->error );
        $this->assertContains( 'players_1_p1', $validator->err_flds );
        $this->assertContains( 'Player registration not found', $validator->err_msgs );
    }

    public function test_validate_team_order_duplicate_player_returns_400(): void {
        $this->setup_event_for_team_order();
        $player1 = $this->create_player_dto( '10.0' );

        $reg_service = $this->createMock( Registration_Service::class );
        $reg_service->expects( $this->exactly( 2 ) )->method( 'get_registration' )
            ->with( 100 )
            ->willReturn( $player1 );

        $this->registration_service = $reg_service;
        $this->adapter              = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1, 2 ],
            'players'    => [
                1 => [ 'p1' => 100 ],
                2 => [ 'p1' => 100 ],
            ],
        ] );
        $this->assertSame( 400, $response->get_status_code() );
        $validator = $response->get_content();
        $this->assertTrue( $validator->error );
        $this->assertContains( 'players_2_p1', $validator->err_flds );
        $this->assertContains( 'Player already selected', $validator->err_msgs );
    }

    public function test_validate_team_order_wtn_calculation_with_default_40_9(): void {
        $this->setup_event_for_team_order();
        $this->registration_service = $this->create_registration_service_for_players( [
            101 => $this->create_player_dto( '15.2' ),
            102 => $this->create_player_dto(), // Missing WTN, should default to 40.9
        ] );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1 ],
            'players'    => [
                1 => [ 'p1' => 101, 'p2' => 102 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 56.1, $content->rubbers[1]->wtn );
        $this->assertSame( 'Valid playing order', $content->msg );
    }

    public function test_validate_team_order_valid_wtn_ordering_assigns_winner_status(): void {
        $this->setup_event_for_team_order();
        // Rubber 1 (WTN 20.0) <= Rubber 2 (WTN 30.0) => Valid Order
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '10.0' ),
            2 => $this->create_player_dto( 10.0 ),
            3 => $this->create_player_dto( 15.0 ),
            4 => $this->create_player_dto( '15.0' ),
        ] );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1, 2 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
                2 => [ 'p1' => 3, 'p2' => 4 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 'Valid playing order', $content->msg );
        $this->assertSame( 20.0, $content->rubbers[1]->wtn );
        $this->assertSame( 30.0, $content->rubbers[2]->wtn );
        $this->assertSame( 'W', $content->rubbers[1]->status );
        $this->assertSame( 'winner', $content->rubbers[1]->status_class );
        $this->assertSame( 'W', $content->rubbers[2]->status );
        $this->assertSame( 'winner', $content->rubbers[2]->status_class );
    }

    public function test_validate_team_order_three_rubbers_valid_exercises_middle_rubber(): void {
        $this->setup_event_for_team_order( 10, 'XD' );
        // Rubber 1: 10 + 10 = 20.0
        // Rubber 2: 15 + 15 = 30.0
        // Rubber 3: 20 + 20 = 40.0
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '10.0' ),
            2 => $this->create_player_dto( 10.0 ),
            3 => $this->create_player_dto( 15.0 ),
            4 => $this->create_player_dto( '15.0' ),
            5 => $this->create_player_dto( 20.0 ),
            6 => $this->create_player_dto( 20.0 ),
        ] );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1, 2, 3 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
                2 => [ 'p1' => 3, 'p2' => 4 ],
                3 => [ 'p1' => 5, 'p2' => 6 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 'Valid playing order', $content->msg );
        $this->assertSame( 20.0, $content->rubbers[1]->wtn );
        $this->assertSame( 30.0, $content->rubbers[2]->wtn );
        $this->assertSame( 40.0, $content->rubbers[3]->wtn );
        $this->assertSame( 'W', $content->rubbers[1]->status );
        $this->assertSame( 'W', $content->rubbers[2]->status );
        $this->assertSame( 'W', $content->rubbers[3]->status );
    }

    public function test_validate_team_order_invalid_wtn_ordering_assigns_loser_status(): void {
        $this->setup_event_for_team_order();
        // Rubber 1 (WTN 35.0) > Rubber 2 (WTN 20.0) => Invalid Order
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '20.0' ),
            2 => $this->create_player_dto( 15.0 ),
            3 => $this->create_player_dto( 10.0 ),
            4 => $this->create_player_dto( '10.0' ),
        ] );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'rubber_num' => [ 1, 2 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
                2 => [ 'p1' => 3, 'p2' => 4 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertFalse( $content->valid );
        $this->assertSame( 'Invalid playing order', $content->msg );
        $this->assertSame( 35.0, $content->rubbers[1]->wtn );
        $this->assertSame( 20.0, $content->rubbers[2]->wtn );
        $this->assertSame( 'L', $content->rubbers[1]->status );
        $this->assertSame( 'loser', $content->rubbers[1]->status_class );
        $this->assertSame( 'L', $content->rubbers[2]->status );
        $this->assertSame( 'loser', $content->rubbers[2]->status_class );
    }

    public function test_validate_team_order_set_team_home_opponent_persists_players(): void {
        $this->setup_event_for_team_order();
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '10.0' ),
            2 => $this->create_player_dto( 10.0 ),
        ] );

        $rubber1_players_called = null;
        $match = \Racketmanager\Domain\Fixture\Fixture_Factory::from_array( [ 'id' => 555, 'home_team' => '100', 'away_team' => '200' ] );
        $rubber = $this->create_rubber_with_recorder( 1, $rubber1_players_called, 1 );
        $this->rubber_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class );
        $this->rubber_repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 555 )->willReturn( [ $rubber ] );
        $this->rubber_repository->expects( $this->once() )->method( 'save_players' )->with( 1, [ 'home' => [ 'p1' => 1, 'p2' => 2 ] ] );

        $this->setup_fixture_repo_for_match( 555, $match );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'teamId'     => 100,
            'matchId'    => 555,
            'setTeam'    => '1',
            'rubber_num' => [ 1 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 'Team players set', $content->msg );
        $this->assertSame( [ 'home' => [ 'p1' => 1, 'p2' => 2 ] ], $rubber1_players_called );
    }

    public function test_validate_team_order_set_team_away_opponent_persists_players(): void {
        $this->setup_event_for_team_order();
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '10.0' ),
            2 => $this->create_player_dto( 10.0 ),
        ] );

        $rubber1_players_called = null;
        $match = \Racketmanager\Domain\Fixture\Fixture_Factory::from_array( [ 'id' => 555, 'home_team' => '100', 'away_team' => '200' ] );
        $rubber = $this->create_rubber_with_recorder( 1, $rubber1_players_called, 1 );
        $this->rubber_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class );
        $this->rubber_repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 555 )->willReturn( [ $rubber ] );
        $this->rubber_repository->expects( $this->once() )->method( 'save_players' )->with( 1, [ 'away' => [ 'p1' => 1, 'p2' => 2 ] ] );

        $this->setup_fixture_repo_for_match( 555, $match );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'teamId'     => 200,
            'matchId'    => 555,
            'setTeam'    => '1',
            'rubber_num' => [ 1 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 'Team players set', $content->msg );
        $this->assertSame( [ 'away' => [ 'p1' => 1, 'p2' => 2 ] ], $rubber1_players_called );
    }

    public function test_validate_team_order_set_team_no_matching_opponent_returns_unable_to_set_team(): void {
        $this->setup_event_for_team_order();
        $this->registration_service = $this->create_registration_service_for_players( [
            1 => $this->create_player_dto( '10.0' ),
            2 => $this->create_player_dto( 10.0 ),
        ] );

        $match = $this->createStub( \Racketmanager\Domain\Fixture\Fixture::class );
        $match->home_team = '100';
        $match->away_team = '200';
        $match->method( 'get_rubbers' )->willReturn( [] );

        $this->setup_fixture_repo_for_match( 555, $match );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_team_order( [
            'security'   => 'valid',
            'eventId'    => 10,
            'teamId'     => 300,
            'matchId'    => 555,
            'setTeam'    => '1',
            'rubber_num' => [ 1 ],
            'players'    => [
                1 => [ 'p1' => 1, 'p2' => 2 ],
            ],
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->valid );
        $this->assertSame( 'Valid playing order but unable to set team', $content->msg );
    }

    public function test_get_player_details_performs_lookup(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $player_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_registered_player_autocomplete' )
            ->with( 'Smith', 'name', 'M', null, 100 )
            ->willReturn( [
                [
                    'id'         => 10,
                    'playerId'   => 1,
                    'name'       => 'John Smith',
                    'user_email' => 'john@example.com',
                    'club_id'    => 100,
                    'club'       => 'Club A',
                    'btm'        => '111',
                    'contactno'  => '01234',
                ],
            ] );

        $handler       = new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler( $player_repo );
        $this->adapter = $this->make_adapter( $handler );

        $response = $this->adapter->get_player_details( [
            'security' => 'valid',
            'type'     => 'name',
            'name'     => 'Smith',
            'gender'   => 'M',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $decoded = json_decode( (string) $response->get_content(), true );
        $this->assertIsArray( $decoded );
        $this->assertSame( 'John Smith', $decoded[0]['value'] );
        $this->assertSame( 'John Smith', $decoded[0]['name'] );
        $this->assertSame( 'John Smith - Club A', $decoded[0]['label'] );
        $this->assertSame( 1, $decoded[0]['playerId'] );
        $this->assertSame( 10, $decoded[0]['id'] );
        $this->assertSame( 100, $decoded[0]['club_id'] );
        $this->assertSame( 'Club A', $decoded[0]['club'] );
        $this->assertArrayNotHasKey( 'user_pass', $decoded[0] );
        $this->assertArrayNotHasKey( 'user_activation_key', $decoded[0] );
    }

    public function test_get_player_details_reads_partner_gender_key(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $player_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_registered_player_autocomplete' )
            ->with( 'Jane', 'name', 'F', null, 100 )
            ->willReturn( [
                [
                    'id'         => 20,
                    'playerId'   => 2,
                    'name'       => 'Jane Doe',
                    'user_email' => 'jane@example.com',
                    'club_id'    => 101,
                    'club'       => 'Club B',
                    'btm'        => '222',
                    'contactno'  => '',
                ],
            ] );

        $handler       = new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler( $player_repo );
        $this->adapter = $this->make_adapter( $handler );

        $response = $this->adapter->get_player_details( [
            'security'      => 'valid',
            'type'          => 'name',
            'name'          => 'Jane',
            'partnerGender' => 'F',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $decoded = json_decode( (string) $response->get_content(), true );
        $this->assertSame( 'Jane Doe', $decoded[0]['value'] );
        $this->assertSame( 2, $decoded[0]['playerId'] );
    }

    public function test_get_player_details_registered_or_club_type_returns_empty_list(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->get_player_details( [
            'security' => 'valid',
            'type'     => 'registered',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( '[]', (string) $response->get_content() );
    }

    public function test_get_player_details_btm_lookup(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $player_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_registered_player_autocomplete' )
            ->with( '999888', 'btm', null, null, 100 )
            ->willReturn( [
                [
                    'id'         => 55,
                    'playerId'   => 10,
                    'name'       => 'Roger Federer',
                    'user_email' => 'roger@example.com',
                    'club_id'    => 105,
                    'club'       => 'Basel Club',
                    'btm'        => '999888',
                    'contactno'  => '0123456789',
                ],
            ] );

        $handler       = new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler( $player_repo );
        $this->adapter = $this->make_adapter( $handler );

        $response = $this->adapter->get_player_details( [
            'security' => 'valid',
            'type'     => 'btm',
            'name'     => '999888',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $decoded = json_decode( (string) $response->get_content(), true );
        $this->assertCount( 1, $decoded );
        $this->assertSame( '999888', $decoded[0]['value'] );
        $this->assertSame( 'Roger Federer', $decoded[0]['name'] );
        $this->assertSame( 'Roger Federer - Basel Club', $decoded[0]['label'] );
        $this->assertSame( 10, $decoded[0]['playerId'] );
        $this->assertSame( 55, $decoded[0]['id'] );
        $this->assertSame( 105, $decoded[0]['club_id'] );
        $this->assertSame( 'Basel Club', $decoded[0]['club'] );
    }

    public function test_get_team_event_info_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $raw_data = (object) [
            'captain_id'         => 1,
            'captain_name'       => 'John',
            'captain_email'      => 'john@example.com',
            'captain_contact_no' => '123',
            'match_day'          => 'Monday',
            'match_time'         => '19:00',
        ];
        $team_info = new \Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO( $raw_data );
        $this->team_service = $this->createMock( Team_Service::class );
        $this->team_service->expects( $this->once() )
            ->method( 'get_latest_team_details_for_event' )
            ->with( 20, 10 )
            ->willReturn( $team_info );

        $this->adapter  = $this->make_adapter();
        $response       = $this->adapter->get_team_event_info( [
            'security' => 'valid',
            'eventId'  => 10,
            'teamId'   => 20,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( $team_info, $content->info );
    }

    public function test_update_team_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->update_team( [ 'racketmanager_nonce' => 'invalid' ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_update_team_direct_input_validation_failure(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->update_team( [
            'racketmanager_nonce' => 'valid',
            'event_id'            => 1,
            'team_id'             => 2,
        ] );
        $this->assertSame( 404, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertTrue( $data->error );
    }

    public function test_update_team_direct_input_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_teams'][20] = (object) [ 'id' => 20 ];

        $team_service = $this->createMock( Team_Service::class );
        $team_service->expects( $this->once() )
            ->method( 'update_team_event' )
            ->with( 20, 10, 5, '07700900111', 'capt@example.com', 1, '19:00' )
            ->willReturn( 'Team successfully updated' );

        $this->team_service = $team_service;
        $this->adapter      = $this->make_adapter();

        $response = $this->adapter->update_team( [
            'racketmanager_nonce'      => 'valid',
            'event_id'                 => 10,
            'team_id'                  => 20,
            'captainId-10-20'          => 5,
            'contactno-10-20'          => '07700900111',
            'contactemail-10-20'       => 'capt@example.com',
            'matchday-10-20'           => 1,
            'matchtime-10-20'          => '19:00',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Team successfully updated', $response->get_content() );
    }

    public function test_update_player_direct_input_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'racketmanager_nonce' => 'valid',
            'playerId'            => 1,
            'firstname'           => 'John',
            'surname'             => 'Doe',
        ];

        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )
            ->method( 'amend_player_details' )
            ->with( 1, $this->callback( function ( array $data ) {
                return ( $data['playerId'] ?? null ) === 1
                    && ( $data['firstname'] ?? null ) === 'John'
                    && ( $data['surname'] ?? null ) === 'Doe';
            } ) )
            ->willReturn( $this->createStub( Player::class ) );

        $this->player_service = $player_service;
        $this->adapter        = $this->make_adapter();

        $response = $this->adapter->update_player( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Player updated', $response->get_content()->msg );
    }

    public function test_update_player_uses_injected_handler(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'racketmanager_nonce' => 'valid',
            'playerId'            => 42,
            'firstname'           => 'Injected',
            'surname'             => 'Handler',
        ];

        $player = $this->createStub( Player::class );
        $update_handler = $this->createMock( \Racketmanager\Application\Player\Commands\Update_Player_Handler::class );
        $update_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( fn( \Racketmanager\Application\Player\Commands\Update_Player_Command $c ) => 42 === $c->player_id && 'Injected' === $c->first_name ) )
            ->willReturn( $player );

        $response_factory = $this->createMock( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 200 )
        );

        $adapter = new Frontend_Ajax_Adapter(
            security_service: $this->security_service,
            response_factory: $response_factory,
            competition_service: $this->competition_service,
            competition_entry_service: $this->competition_entry_service,
            team_service: $this->team_service,
            player_service: $this->player_service,
            registration_service: $this->registration_service,
            club_service: $this->club_service,
            tournament_service: $this->tournament_service,
            fixture_repository: $this->fixture_repository,
            rubber_repository: $this->rubber_repository,
            search_players_handler: $this->createStub( \Racketmanager\Application\Player\Queries\Search_Players_Handler::class ),
            get_player_details_handler: $this->createStub( \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler::class ),
            update_player_handler: $update_handler
        );

        $response = $adapter->update_player( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Player updated', $response->get_content()->msg );
    }

    public function test_search_players_uses_injected_handler(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'security'      => 'valid',
            'search_string' => 'Federer',
        ];

        $search_handler = $this->createMock( \Racketmanager\Application\Player\Queries\Search_Players_Handler::class );
        $search_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( fn( \Racketmanager\Application\Player\Queries\Search_Players_Query $q ) => 'Federer' === $q->search_string ) )
            ->willReturn( '<div class="players">Federer Results</div>' );

        $response_factory = $this->createMock( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_raw_response' )->willReturnCallback(
            static fn( $content, $status = null ) => new Response( $content, $status ?? 200 )
        );

        $adapter = new Frontend_Ajax_Adapter(
            security_service: $this->security_service,
            response_factory: $response_factory,
            competition_service: $this->competition_service,
            competition_entry_service: $this->competition_entry_service,
            team_service: $this->team_service,
            player_service: $this->player_service,
            registration_service: $this->registration_service,
            club_service: $this->club_service,
            tournament_service: $this->tournament_service,
            fixture_repository: $this->fixture_repository,
            rubber_repository: $this->rubber_repository,
            search_players_handler: $search_handler,
            get_player_details_handler: $this->createStub( \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler::class )
        );

        $response = $adapter->search_players( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( '<div class="players">Federer Results</div>', $response->get_content() );
    }

    public function test_get_player_details_uses_injected_handler(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'security' => 'valid',
            'type'     => 'name',
            'name'     => 'Serena',
            'gender'   => 'F',
        ];

        $details_handler = $this->createMock( \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler::class );
        $details_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( fn( \Racketmanager\Application\Player\Queries\Get_Player_Details_Query $q ) => 'name' === $q->type && 'Serena' === $q->name && 'F' === $q->gender ) )
            ->willReturn( [ [ 'id' => 10, 'name' => 'Serena' ] ] );

        $response_factory = $this->createMock( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 200 )
        );

        $adapter = new Frontend_Ajax_Adapter(
            security_service: $this->security_service,
            response_factory: $response_factory,
            competition_service: $this->competition_service,
            competition_entry_service: $this->competition_entry_service,
            team_service: $this->team_service,
            player_service: $this->player_service,
            registration_service: $this->registration_service,
            club_service: $this->club_service,
            tournament_service: $this->tournament_service,
            fixture_repository: $this->fixture_repository,
            rubber_repository: $this->rubber_repository,
            search_players_handler: $this->createStub( \Racketmanager\Application\Player\Queries\Search_Players_Handler::class ),
            get_player_details_handler: $details_handler
        );

        $response = $adapter->get_player_details( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( wp_json_encode( [ [ 'id' => 10, 'name' => 'Serena' ] ] ), $response->get_content() );
    }

    public function test_constructor_requires_player_query_handlers(): void {
        $ctor = new \ReflectionMethod( Frontend_Ajax_Adapter::class, '__construct' );
        $params = [];
        foreach ( $ctor->getParameters() as $param ) {
            $params[ $param->getName() ] = $param;
        }

        $this->assertArrayNotHasKey( 'player_repository', $params, 'Adapter must not build handlers from a repository.' );
        foreach ( [ 'search_players_handler' => \Racketmanager\Application\Player\Queries\Search_Players_Handler::class, 'get_player_details_handler' => \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler::class ] as $name => $class ) {
            $this->assertArrayHasKey( $name, $params );
            $this->assertFalse( $params[ $name ]->isOptional(), "$name must be a required constructor argument." );
            $this->assertFalse( $params[ $name ]->allowsNull(), "$name must not be nullable." );
            $this->assertSame( $class, (string) $params[ $name ]->getType() );
        }
        $this->assertFalse( method_exists( Player_Service::class, 'get_player_repository' ), 'Player_Service must not expose its repository.' );
    }

    public function test_direct_construction_without_details_handler_fails_fast(): void {
        $this->expectException( \ArgumentCountError::class );
        new Frontend_Ajax_Adapter(
            security_service: $this->security_service,
            response_factory: $this->createStub( Json_Response_Factory_Interface::class ),
            competition_service: $this->competition_service,
            competition_entry_service: $this->competition_entry_service,
            team_service: $this->team_service,
            player_service: $this->player_service,
            registration_service: $this->registration_service,
            club_service: $this->club_service,
            tournament_service: $this->tournament_service,
            fixture_repository: $this->fixture_repository,
            rubber_repository: $this->rubber_repository,
            search_players_handler: $this->createStub( \Racketmanager\Application\Player\Queries\Search_Players_Handler::class )
        );
    }

    public function test_get_player_details_name_type_returns_json_string_without_secrets(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $player_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_registered_player_autocomplete' )
            ->with( 'jo', 'name', 'M', null, 100 )
            ->willReturn( [
                [
                    'id'                  => 50,
                    'playerId'            => 5,
                    'name'                => 'Jo Bloggs',
                    'user_email'          => 'jo@example.com',
                    'club_id'             => 100,
                    'club'                => 'Club Jo',
                    'btm'                 => '999',
                    'contactno'           => '07700',
                    'user_pass'           => '$P$secret',
                    'user_activation_key' => 'activation-secret',
                    'year_of_birth'       => '1990',
                ],
            ] );

        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->never() )->method( 'get_all_players' );
        $reg_service = $this->createMock( Registration_Service::class );
        $reg_service->expects( $this->never() )->method( 'get_clubs_for_player' );

        $this->player_service       = $player_service;
        $this->registration_service = $reg_service;
        $this->adapter              = $this->make_adapter(
            new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler( $player_repo )
        );

        $response = $this->adapter->get_player_details( [
            'security'      => 'valid',
            'type'          => 'name',
            'name'          => 'jo',
            'partnerGender' => 'M',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertIsString( $content );
        $decoded = json_decode( $content, true );
        $this->assertIsArray( $decoded );
        $this->assertSame( 'Jo Bloggs - Club Jo', $decoded[0]['label'] );
        $this->assertSame( 'Jo Bloggs', $decoded[0]['value'] );
        $this->assertSame( 'Jo Bloggs', $decoded[0]['name'] );
        $this->assertSame( 5, $decoded[0]['playerId'] );
        $this->assertSame( 50, $decoded[0]['id'] );
        $this->assertSame( 100, $decoded[0]['club_id'] );
        $this->assertSame( 'Club Jo', $decoded[0]['club'] );
        $this->assertSame( '999', $decoded[0]['btm'] );
        $this->assertSame( '07700', $decoded[0]['contactno'] );
        $this->assertSame( 'jo@example.com', $decoded[0]['user_email'] );
        $this->assertSame(
            [ 'label', 'value', 'name', 'id', 'playerId', 'btm', 'contactno', 'user_email', 'club_id', 'club' ],
            array_keys( $decoded[0] )
        );
        $this->assertStringNotContainsString( 'user_pass', $content );
        $this->assertStringNotContainsString( 'user_activation_key', $content );
        $this->assertStringNotContainsString( 'secret', $content );
        $this->assertStringNotContainsString( 'year_of_birth', $content );
    }

    public function test_get_player_details_empty_name_returns_empty_json_array(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $player_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repo->expects( $this->never() )->method( 'find_registered_player_autocomplete' );
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->never() )->method( 'get_all_players' );

        $this->player_service = $player_service;
        $this->adapter        = $this->make_adapter(
            new \Racketmanager\Application\Player\Queries\Get_Player_Details_Handler( $player_repo )
        );

        $response = $this->adapter->get_player_details( [
            'security' => 'valid',
            'type'     => 'name',
            'name'     => '',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( '[]', $response->get_content() );
        $this->assertSame( [], json_decode( (string) $response->get_content(), true ) );
    }

    public function test_update_player_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->update_player( [ 'racketmanager_nonce' => 'invalid' ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_nonce_failure_missing_nonce_returns_403_with_generic_problem_message(): void {
        // Missing nonce entirely from request array
        $response = $this->adapter->update_player( [] );
        $this->assertSame( 403, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertStringContainsString( 'There was a problem with your request', $content->msg );
    }

    public function test_nonce_failure_invalid_nonce_returns_403_with_expired_invalid_message(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->update_player( [ 'racketmanager_nonce' => 'invalid_token' ] );
        $this->assertSame( 403, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertStringContainsString( 'expired or is invalid', $content->msg );
    }

    public function test_cup_entry_success_when_logged_in_maps_dto(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'racketmanager_nonce' => 'valid',
            'competitionId'       => 5,
            'season'              => '2026',
            'clubId'              => 12,
            'commentDetails'      => 'Entering cup',
            'acceptance'          => '1',
            'event'               => [ 101 ],
            'team'                => [ 101 => 201 ],
            'captain'             => [ 101 => 'Alice Captain' ],
            'captainId'           => [ 101 => 301 ],
            'contactno'           => [ 101 => '01234567890' ],
            'contactemail'        => [ 101 => 'alice@example.com' ],
            'matchday'            => [ 101 => 2 ],
            'matchtime'           => [ 101 => '18:30' ],
        ];

        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->once() )
            ->method( 'request_cup_entry' )
            ->with( $this->callback( function ( Cup_Entry_Request_DTO $dto ) {
                return $dto->competition_id === 5
                    && $dto->season === '2026'
                    && $dto->club_id === 12
                    && $dto->comments === 'Entering cup'
                    && $dto->acceptance === true
                    && isset( $dto->events_entered[101] )
                    && $dto->events_entered[101]->team_id === 201
                    && $dto->events_entered[101]->captain_id === 301
                    && $dto->events_entered[101]->email === 'alice@example.com';
            } ) )
            ->willReturn( true );

        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();

        $response = $this->adapter->cup_entry( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Cup entry complete', $response->get_content() );
    }

    public function test_cup_entry_fails_when_not_logged_in(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->cup_entry( [ 'racketmanager_nonce' => 'valid' ] );
        $this->assertSame( 400, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertTrue( $data->error );
    }

    public function test_cup_entry_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->cup_entry( [ 'racketmanager_nonce' => 'invalid' ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_league_entry_success_when_logged_in_maps_dto(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $request = [
            'racketmanager_nonce' => 'valid',
            'competitionId'       => 6,
            'season'              => '2026',
            'clubId'              => 14,
            'numCourtsAvailable'  => 4,
            'commentDetails'      => 'League comments',
            'acceptance'          => '1',
            'competition_events'  => '201,202',
            'event'               => [ 201 ],
            'teamEvent'           => [ 201 => [ 501 ] ],
            'teamEventTitle'      => [ 201 => [ 501 => 'Men A' ] ],
            'teamEventLeague'     => [ 201 => [ 501 => 1 ] ],
            'captain'             => [ 201 => [ 501 => 'Bob Captain' ] ],
            'captainId'           => [ 201 => [ 501 => 601 ] ],
            'contactno'           => [ 201 => [ 501 => '07700900222' ] ],
            'contactemail'        => [ 201 => [ 501 => 'bob@example.com' ] ],
            'matchday'            => [ 201 => [ 501 => 3 ] ],
            'matchtime'           => [ 201 => [ 501 => '19:30' ] ],
        ];

        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->once() )
            ->method( 'request_league_entry' )
            ->with( $this->callback( function ( League_Entry_Request_DTO $dto ) {
                return $dto->competition_id === 6
                    && $dto->season === '2026'
                    && $dto->club_id === 14
                    && $dto->num_courts_available === 4
                    && $dto->comments === 'League comments'
                    && $dto->acceptance === true
                    && isset( $dto->events_entered[201][501] )
                    && $dto->events_entered[201][501]->team_name === 'Men A'
                    && $dto->events_entered[201][501]->email === 'bob@example.com';
            } ) )
            ->willReturn( true );

        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();

        $response = $this->adapter->league_entry( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'League entry complete', $response->get_content() );
    }

    public function test_league_entry_fails_when_not_logged_in(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->league_entry( [ 'racketmanager_nonce' => 'valid' ] );
        $this->assertSame( 400, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertTrue( $data->error );
    }

    public function test_league_entry_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->league_entry( [ 'racketmanager_nonce' => 'invalid' ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_show_team_edit_modal_direct_input(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->show_team_edit_modal( [
            'security' => 'valid',
            'teamId'   => 1,
            'eventId'  => 2,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'modal-team-edit', (string) $response->get_content() );
    }

    public function test_get_event_team_match_dropdown_direct_input(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $response = $this->adapter->get_event_team_match_dropdown( [
            'security' => 'valid',
            'eventId'  => 2,
            'teamId'   => 1,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'team_matches', (string) $response->get_content() );
    }
}
