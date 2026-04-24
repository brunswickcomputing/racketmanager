<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Response;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Response;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

class Fixture_Ajax_Adapter_Test extends TestCase {
    private Simple_Container $container;
    private Stub|Security_Service_Interface $security_service;
    private MockObject|Fixture_Detail_Service $fixture_detail_service;
    private MockObject|View_Renderer_Interface $view_renderer;
    private Fixture_Ajax_Adapter $adapter;

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_fails_security_check(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $response = $this->adapter->print_match_card();
        $data     = $response->get_content();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'Security', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_missing_id(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';

        $response = $this->adapter->print_match_card();
        $data     = $response->get_content();
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertStringContainsString( 'not supplied', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId']             = 123;

        $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 123 )->willReturn( null );

        $response = $this->adapter->print_match_card();
        $data     = $response->get_content();
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertStringContainsString( 'not found', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_success_no_rubbers(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId']             = 123;

        $fixture             = $this->createStub( Fixture::class );
        $league              = $this->createStub( League::class );
        $league->num_rubbers = 0;

        $dto = new Fixture_Details_DTO( $fixture, $league, $this->createStub( Event::class ), $this->createStub( Competition::class ) );

        $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'match/match-card', $this->callback( function ( $args ) use ( $dto, $fixture ) {
                return $args['dto'] === $dto && $args['match'] === $fixture;
            } ) )->willReturn( 'Rendered Content' );

        $response = $this->adapter->print_match_card();
        $this->assertSame( 'Rendered Content', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_success_with_rubbers(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId']             = 123;

        $fixture = $this->createMock( Fixture::class );
        $fixture->expects( $this->once() )->method( 'get_rubbers' )->willReturn( [ 'rubber1' ] );

        $league              = $this->createStub( League::class );
        $league->num_rubbers = 3;

        $dto = new Fixture_Details_DTO( $fixture, $league, $this->createStub( Event::class ), $this->createStub( Competition::class ) );

        $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'match/match-card-rubbers', $this->callback( function ( $args ) use ( $dto, $fixture ) {
                return $args['dto'] === $dto && $args['match'] === $fixture && $fixture->rubbers === [ 'rubber1' ];
            } ) )->willReturn( 'Rendered Rubbers Content' );

        $response = $this->adapter->print_match_card();
        $this->assertSame( 'Rendered Rubbers Content', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_update_fixture_result_fails_security_check(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $response = $this->adapter->update_fixture_result();
        $data     = $response->get_content();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'Security', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_update_fixture_result_fixture_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['current_match_id']    = 123;

        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( null );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $response = $this->adapter->update_fixture_result();
        $data     = $response->get_content();
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertStringContainsString( 'not found', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_status_fails_security_check(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $response = $this->adapter->set_fixture_status();
        $data     = $response->get_content();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'Security', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_status_invalid_data(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']            = 0;

        $response = $this->adapter->set_fixture_status();
        $data     = $response->get_content();
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertNotEmpty( $data['err_msgs'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security'] = 'valid';
        $_POST['match_id'] = 123;
        $_POST['modal']    = 'test-modal';

        $fixture     = $this->createStub( Fixture::class );
        $league      = $this->createStub( League::class );
        $event       = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $dto         = new Fixture_Details_DTO( $fixture, $league, $event, $competition );

        $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'match/match-status-modal', $this->callback( function ( $vars ) use ( $dto ) {
                return $vars['dto'] === $dto && $vars['modal'] === 'test-modal';
            } ) )->willReturn( '<html lang="">modal content</html>' );

        $response = $this->adapter->fixture_status_options();
        $this->assertSame( '<html lang="">modal content</html>', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_fails_validation(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security'] = 'valid';
        // Missing match_id and modal

        $response = $this->adapter->fixture_status_options();
        $data     = $response->get_content();
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertStringContainsString( 'Match id not found', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_match_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security'] = 'valid';
        $_POST['match_id'] = 999;
        $_POST['modal']    = 'test-modal';

        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( null );

        $response = $this->adapter->fixture_status_options();
        $data     = $response->get_content();
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertStringContainsString( 'not found', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_status_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']            = 123;
        $_POST['score_status']        = 'walkover_player1';
        $_POST['modal']               = 'some_modal';

        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->method( 'get_home_team' )->willReturn( '1' );
        $fixture->method( 'get_away_team' )->willReturn( '2' );
        $fixture->method( 'get_league_id' )->willReturn( 456 );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( $this->once() )->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $league              = $this->createStub( League::class );
        $league->num_rubbers = 3;
        $league_repo         = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );
        $this->container->set( 'league_repository', $league_repo );

        $response = $this->adapter->set_fixture_status();
        $data     = $response->get_content();
        $this->assertSame( 123, $data['match_id'] );
        $this->assertSame( 'walkover_player1', $data['match_status'] );
        $this->assertSame( 3, $data['num_rubbers'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_rubber_status_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']            = 123;
        $_POST['score_status']        = 'retired_player2';
        $_POST['rubber_number']       = 1;

        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->method( 'get_home_team' )->willReturn( '1' );
        $fixture->method( 'get_away_team' )->willReturn( '2' );
        $fixture->method( 'get_league_id' )->willReturn( 456 );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( $this->once() )->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $league              = $this->createStub( League::class );
        $league->num_rubbers = 3;
        $league_repo         = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );
        $this->container->set( 'league_repository', $league_repo );

        $response = $this->adapter->set_rubber_status();
        $data     = $response->get_content();
        $this->assertSame( 123, $data['match_id'] );
        $this->assertSame( 1, $data['rubber_number'] );
        $this->assertSame( 'retired_player2', $data['match_status'] );
        $this->assertSame( 'retired_player2', $data['score_status'] );
        $this->assertSame( 3, $data['num_rubbers'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security']  = 'valid';
        $_POST['rubber_id'] = 456;
        $_POST['modal']     = 'rubberStatusModal';

        $rubber           = new stdClass();
        $rubber->match_id = 123;

        // Eval-based stub for get_rubber since it is a global function
        $GLOBALS['wp_stubs_rubbers'] = [ 456 => $rubber ];
        if ( ! function_exists( 'Racketmanager\get_rubber' ) ) {
            eval( 'namespace Racketmanager { function get_rubber($id) { return $GLOBALS["wp_stubs_rubbers"][$id] ?? null; } }' );
        }

        $league      = $this->createStub( League::class );
        $fixture     = $this->createStub( Fixture::class );
        $event       = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $dto         = new Fixture_Details_DTO( $fixture, $league, $event, $competition );

        $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'match/rubber-status-modal', $this->callback( function ( $vars ) use ( $dto, $rubber ) {
                return $vars['dto'] === $dto && $vars['rubber'] === $rubber && $vars['not_played'] === 'Not played';
            } ) )->willReturn( '<html lang="">Modal Content</html>' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( '<html lang="">Modal Content</html>', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_fails_validation(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security'] = 'valid';
        // Missing rubber_id

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'alert-modal', $this->callback( function ( $vars ) {
                return isset( $vars['msg'] ) && $vars['class'] === 'danger';
            } ) )->willReturn( 'ALERT: rubber_id is required' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertSame( 'ALERT: rubber_id is required', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_security_fails(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'alert-modal', $this->callback( function ( $vars ) {
                return $vars['msg'] === 'Security check failed' && $vars['class'] === 'danger';
            } ) )->willReturn( 'ALERT: Security check failed' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertSame( 'ALERT: Security check failed', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_rubber_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security']  = 'valid';
        $_POST['rubber_id'] = 999;
        $_POST['modal']     = 'someModal';

        $GLOBALS['wp_stubs_rubbers'] = [];

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'alert-modal', $this->callback( function ( $vars ) {
                return $vars['msg'] === 'Rubber not found' && $vars['class'] === 'danger';
            } ) )->willReturn( 'ALERT: Rubber not found' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'ALERT: Rubber not found', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_match_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security']  = 'valid';
        $_POST['rubber_id'] = 456;
        $_POST['modal']     = 'someModal';

        $rubber                      = new stdClass();
        $rubber->match_id            = 123;
        $GLOBALS['wp_stubs_rubbers'] = [ 456 => $rubber ];

        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( null );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'alert-modal', $this->callback( function ( $vars ) {
                return $vars['msg'] === 'Fixture not found' && $vars['class'] === 'danger';
            } ) )->willReturn( 'ALERT: Fixture not found' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'ALERT: Fixture not found', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_unexpected_error(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $_POST['security']  = 'valid';
        $_POST['rubber_id'] = 456;
        $_POST['modal']     = 'someModal';

        $rubber                      = new stdClass();
        $rubber->match_id            = 123;
        $GLOBALS['wp_stubs_rubbers'] = [ 456 => $rubber ];

        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willThrowException( new Exception( 'Boom' ) );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )->with( 'alert-modal', $this->callback( function ( $vars ) {
                return $vars['msg'] === 'An unexpected error occurred' && $vars['class'] === 'danger';
            } ) )->willReturn( 'ALERT: Unexpected' );

        $response = $this->adapter->match_rubber_status_options();
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'ALERT: Unexpected', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_show_match_option_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $_POST['match_id']                          = 123;
        $_POST['modal']                             = 'test-modal';
        $_POST['option']                            = 'schedule_match';
        $_REQUEST['racketmanager_nonce']            = 'valid';

        $fixture = $this->createMock( Fixture::class );
        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $competition = $this->createMock( Competition::class );
        
        $dto = new Fixture_Details_DTO(
            $fixture,
            $league,
            $event,
            $competition
        );
        
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $this->view_renderer->expects( $this->once() )->method( 'render_to_string' )
            ->with( 'match/match-option-modal', $this->callback( function ( $vars ) use ( $dto ) {
                return $vars['dto'] === $dto && $vars['title'] === '(Re)schedule fixture' && $vars['action'] === 'setMatchDate';
            } ) )->willReturn( 'rendered content' );

        $response = $this->adapter->show_fixture_option();
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'rendered content', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_update_match_header_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $GLOBALS['wp_stubs_matches'][123]           = (object) [ 'id' => 123 ];
        $_POST['match_id']                          = 123;
        $_REQUEST['security']                       = 'valid';

        $response = $this->adapter->update_fixture_header();
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'match header content', $response->get_content() );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_date_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $_POST['racketmanager_nonce']               = 'valid';
        $_POST['match_id']                          = 123;
        $_POST['modal']                             = 'test-modal';
        $_POST['schedule-date']                     = '2023-10-10';

        $match        = $this->getMockBuilder( Fixture::class )->disableOriginalConstructor()->getMock();
        $match->id    = 123;
        $match->method('get_date')->willReturn('2023-10-09');

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->with( 123 )->willReturn( $match );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $maintenance_service->expects( $this->once() )->method( 'update_fixture_date' )->with( 123, '2023-10-10', '2023-10-09' );
        $maintenance_service->expects( $this->once() )->method( 'update_fixture_status' )->with( 123, 5 );
        $this->container->set( 'fixture_maintenance_service', $maintenance_service );

        $response = $this->adapter->set_fixture_date();
        $this->assertSame( 200, $response->get_status_code(), 'Response should be 200, content: ' . json_encode( $response->get_content() ) );
        $data = $response->get_content();
        $this->assertSame( 'Match schedule updated', $data['msg'] );
        $this->assertSame( 123, $data['match_id'] );
        $this->assertSame( '2023-10-10', $data['schedule_date'] );
        $this->assertSame( 'Tue 10 Oct', $data['schedule_date_formated'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_switch_home_away_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $_POST['racketmanager_nonce']               = 'valid';
        $_REQUEST['racketmanager_nonce']            = 'valid';
        $_POST['match_id']                          = 123;
        $_POST['modal']                             = 'test-modal';

        $league = $this->getMockBuilder( League::class )->disableOriginalConstructor()->getMock();
        $league->method( 'get_event_id' )->willReturn( 456 );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->getMock();

        $match = $this->getMockBuilder( Fixture::class )->disableOriginalConstructor()->getMock();
        $match->method( 'get_id' )->willReturn( 123 );
        $match->method( 'get_home_team' )->willReturn( '1' );
        $match->method( 'get_away_team' )->willReturn( '2' );
        $match->method( 'get_season' )->willReturn( '2023' );
        $match->method( 'get_match_day' )->willReturn( 1 );
        $match->method( 'get_league_id' )->willReturn( 789 );

        $dto = new Fixture_Details_DTO(
            $match,
            $league,
            $this->createStub( Event::class ),
            $this->createStub( Competition::class ),
            link: 'https://example.com'
        );

        $this->fixture_detail_service->method( 'get_fixture_with_details' )->with( 123 )->willReturn( $dto );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->with( 123 )->willReturn( $match );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->with( 789 )->willReturn( $league );
        $this->container->set( 'league_repository', $league_repo );

        $event_repo = $this->createMock( Event_Repository_Interface::class );
        $event_repo->method( 'find_by_id' )->with( 456 )->willReturn( $event );
        $this->container->set( 'event_repository', $event_repo );

        $event->method( 'get_season_by_name' )->with( '2023' )->willReturn( [
            'match_dates' => [ '2023-10-10' ]
        ] );

        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $maintenance_service->expects( $this->once() )->method( 'update_fixture_date' )->with( 123, '2023-10-10' );
        $maintenance_service->expects( $this->once() )->method( 'update_fixture_teams' )->with( 123, '2', '1' );
        $this->container->set( 'fixture_maintenance_service', $maintenance_service );

        $response = $this->adapter->switch_home_away();
        $this->assertSame( 200, $response->get_status_code(), 'Response should be 200, content: ' . json_encode( $response->get_content() ) );
        $data = $response->get_content();
        $this->assertSame( 'Home and away teams switched', $data['msg'] );
        $this->assertSame( 123, $data['match_id'] );
        $this->assertSame( '', $data['link'] );
        $this->assertSame( 'test-modal', $data['modal'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_reset_match_result_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $_POST['racketmanager_nonce']               = 'valid';
        $_REQUEST['racketmanager_nonce']            = 'valid';
        $_POST['match_id']                          = 123;
        $_POST['modal']                             = 'test-modal';

        $fixture = $this->getMockBuilder( Fixture::class )->disableOriginalConstructor()->onlyMethods( [ 'get_id' ] )->getMock();
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->league_id = 1;

        $fixture_repo    = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $result_response = new Fixture_Reset_Response( 123, Fixture_Reset_Status::SUCCESS_DIVISION_RESET );
        $result_manager  = $this->getMockBuilder( Fixture_Result_Manager::class )->disableOriginalConstructor()->getMock();
        $result_manager->method( 'reset_result' )->with( $fixture )->willReturn( $result_response );
        $this->container->set( 'fixture_result_manager', $result_manager );

        $response = $this->adapter->reset_fixture_result();
        $this->assertSame( 200, $response->get_status_code(), 'Response should be 200, content: ' . json_encode( $response->get_content() ) );
        $data = $response->get_content();
        $this->assertSame( 123, $data['match_id'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_update_team_match_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $_POST['racketmanager_nonce']               = 'valid';
        $_REQUEST['racketmanager_nonce']            = 'valid';
        $_POST['current_match_id']                  = 123;
        $_POST['updateRubber']                      = 'results';

        $league = (object) [ 'id' => 1 ];

        $match                            = (object) [
            'id'     => 123,
            'league' => $league
        ];
        $GLOBALS['wp_stubs_matches'][123] = $match;

        $fixture = $this->getMockBuilder( Fixture::class )->disableOriginalConstructor()->onlyMethods( [ 'get_id' ] )->getMock();
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->league_id = 1;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );
        $this->container->set( 'fixture_repository', $fixture_repo );

        $result_manager  = $this->getMockBuilder( Fixture_Result_Manager::class )->disableOriginalConstructor()->getMock();
        $result_response = new Team_Result_Response( [ 'msg' => 'Fixture details updated' ] );
        $result_manager->method( 'handle_team_result_update' )->willReturn( $result_response );
        $this->container->set( 'fixture_result_manager', $result_manager );

        $response = $this->adapter->update_team_match();
        $this->assertSame( 200, $response->get_status_code(), 'Response should be 200, content: ' . json_encode( $response->get_content() ) );
        $data = (array) $response->get_content();
        $this->assertSame( 'Fixture details updated', $data['msg'] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_logged_out(): void {
        $response = $this->adapter->logged_out();
        $this->assertSame( 401, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertIsArray( $data );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_logged_out_modal(): void {
        $response = $this->adapter->logged_out_modal();
        $this->assertSame( 401, $response->get_status_code() );
        $data = $response->get_content();
        $this->assertIsArray( $data );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
        $this->assertStringContainsString( 'alert', $data[1] );
    }

    protected function setUp(): void {
        parent::setUp();
        $this->container        = new Simple_Container();
        $this->security_service = $this->createStub( Security_Service_Interface::class );
        $response_factory = $this->createStub( Json_Response_Factory_Interface::class );

        $response_factory->method( 'create_success_response' )->willReturnCallback( function ( $data, $status = null ) {
            return new Response( $data, $status );
        } );
        $response_factory->method( 'create_error_response' )->willReturnCallback( function ( $data, $status = null ) {
            return new Response( $data, $status );
        } );
        $response_factory->method( 'create_raw_response' )->willReturnCallback( function ( $content, $status = null ) {
            return new Response( $content, $status );
        } );

        $this->fixture_detail_service = $this->createMock( Fixture_Detail_Service::class );
        $this->view_renderer          = $this->createMock( View_Renderer_Interface::class );
        $link_service                 = $this->createStub( Fixture_Link_Service::class );
        $presenter                    = new Fixture_Presenter( $link_service );
        $this->adapter                = new Fixture_Ajax_Adapter( $this->container, $this->security_service, $response_factory, $this->fixture_detail_service, $this->view_renderer, $presenter );
        $_POST                        = [];
    }
}
