<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Player\User;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;
use WP_Error;

class Club_Ajax_Adapter_Test extends TestCase {
    private Stub|Security_Service_Interface $security_service;
    private Club_Service|MockObject|Stub $club_service;
    private Registration_Service|MockObject|Stub $registration_service;
    private User_Repository_Interface|MockObject|Stub $user_repository;
    private Club_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service     = $this->createStub( Security_Service_Interface::class );
        $this->club_service         = $this->createStub( Club_Service::class );
        $this->registration_service = $this->createStub( Registration_Service::class );
        $this->user_repository      = $this->createStub( User_Repository_Interface::class );
        $this->adapter              = $this->make_adapter();
        $_POST                      = array();
        $_REQUEST                   = array();
        unset( $GLOBALS['wp_stubs_clubs'], $GLOBALS['wp_stubs_players'], $GLOBALS['wp_stubs_users'], $GLOBALS['wp_stubs_club_roles'] );
    }

    private function make_adapter(): Club_Ajax_Adapter {
        $response_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static function ( $data, $status = null ) {
                return new Response( $data, $status ?? 200 );
            }
        );
        $response_factory->method( 'create_error_response' )->willReturnCallback(
            static function ( $data, $status = null ) {
                return new Response( $data, $status ?? 400 );
            }
        );
        $response_factory->method( 'create_raw_response' )->willReturnCallback(
            static function ( $content, $status = null ) {
                return new Response( $content, $status ?? 200 );
            }
        );

        return new Club_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $this->club_service,
            $this->registration_service,
            $this->user_repository
        );
    }

    public function test_register_hooks_uses_single_priv_and_nopriv_ownership(): void {
        $controller = new Club_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = array();
        $controller->register_hooks( $registry );

        $this->assertSame( array( $controller, 'update_club' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_update_club'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_update_club'][0] );
        $this->assertSame( array( $controller, 'club_player_request' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_club_player_request'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_club_player_request'][0] );
        $this->assertSame( array( $controller, 'club_player_remove' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_club_players_remove'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_club_players_remove'][0] );
        $this->assertSame( array( $controller, 'show_club_role_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_club_role_modal'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_club_role_modal'][0] );
        $this->assertSame( array( $controller, 'set_club_role' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_set_club_role'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_set_club_role'][0] );
        $this->assertCount( 1, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_update_club'] );
    }

    public function test_update_club_rejects_invalid_nonce(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $request = array( 'racketmanager_nonce' => 'bad' );

        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'update_club' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->update_club( $request );
        $data     = $response->get_content();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertSame( 'Serious error in club update', $data->msg );
    }

    public function test_update_club_success_maps_command_and_updates_service(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array(
            'racketmanager_nonce'     => 'valid',
            'club_id'                 => 12,
            'clubContactNo'           => '012345',
            'facilities'              => '4 courts',
            'founded'                 => 1970,
            'website'                 => 'https://example.test',
            'address'                 => '1 Court Road',
            'matchSecretaryId'        => 44,
            'matchSecretaryContactNo' => '07700',
            'matchSecretaryEmail'     => 'sec@example.test',
        );
        $GLOBALS['wp_stubs_clubs'][12]     = (object) array( 'id' => 12 );
        $GLOBALS['wp_stubs_clubs']['12']   = $GLOBALS['wp_stubs_clubs'][12];
        $GLOBALS['wp_stubs_players'][44]   = (object) array( 'ID' => 44, 'display_name' => 'Sec' );

        $this->club_service = $this->createMock( Club_Service::class );
        $club               = $this->createStub( Club::class );
        $club->id           = 12;
        $this->club_service->method( 'get_club' )->willReturn( $club );
        $this->club_service->expects( $this->once() )
            ->method( 'update_club' )
            ->with(
                12,
                $this->callback(
                    static function ( $updated ) {
                        return $updated->contactno === '012345'
                            && $updated->address === '1 Court Road'
                            && $updated->match_secretary->id === 44;
                    }
                )
            )
            ->willReturn( $club );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->update_club( $request );
        $data     = $response->get_content();
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Club updated', $data['msg'] );
        $this->assertSame( 'success', $data['status'] );
    }

    public function test_club_player_request_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'racketmanager_nonce' => 'valid', 'club' => 9 );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->registration_service->expects( $this->once() )
            ->method( 'register_player_to_club' )
            ->with( 9, 1, $request )
            ->willReturn( 'Player registered' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->club_player_request( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Player registered', $response->get_content()->msg );
    }

    public function test_club_player_request_service_error(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'racketmanager_nonce' => 'valid', 'club' => 9 );
        $this->registration_service = $this->createStub( Registration_Service::class );
        $this->registration_service->method( 'register_player_to_club' )
            ->willReturn( new WP_Error( 'already', 'Already registered' ) );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->club_player_request( $request );
        $data     = $response->get_content();
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertSame( 'Error in player registration', $data->msg );
    }

    public function test_club_player_request_not_found_exception(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'racketmanager_nonce' => 'valid', 'club' => 99 );
        $this->registration_service = $this->createStub( Registration_Service::class );
        $this->registration_service->method( 'register_player_to_club' )
            ->willThrowException( new Club_Not_Found_Exception( 'Club missing' ) );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->club_player_request( $request );
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertSame( 'Club missing', $response->get_content()->msg );
    }

    public function test_club_player_remove_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'racketmanager_nonce' => 'valid', 'clubPlayer' => array( 3, 4 ) );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->registration_service->expects( $this->exactly( 2 ) )->method( 'remove_registration' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->club_player_remove( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Players removed', $response->get_content() );
    }

    public function test_logged_out_payload(): void {
        $response = $this->adapter->logged_out();
        $data     = $response->get_content();
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
    }

    private function role_post(): array {
        return array(
            'racketmanager_nonce' => 'valid',
            'clubId'              => 12,
            'userId'              => 5,
            'clubRoleId'          => 9,
            'contactno'           => '07700',
            'contactemail'        => 'role@example.test',
        );
    }

    private function stub_role_user( bool $contact_updated, ?object &$user = null ): void {
        $user = new User();
        $user->id         = 5;
        $user->ID         = 5;
        $user->user_email = 'old@example.test';
        $user->email      = 'old@example.test';
        $this->user_repository = $this->createStub( User_Repository_Interface::class );
        $this->user_repository->method( 'find' )->willReturn( $user );
        $this->user_repository->method( 'update_contact' )->willReturn( $contact_updated );
        $GLOBALS['wp_stubs_clubs'][12]   = (object) array( 'id' => 12 );
        $GLOBALS['wp_stubs_clubs']['12'] = $GLOBALS['wp_stubs_clubs'][12];
        $GLOBALS['wp_stubs_users'][5]    = $user;
    }

    public function test_update_club_validation_error_returns_status_and_message(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'racketmanager_nonce' => 'valid', 'club_id' => 0 );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'update_club' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->update_club( $request );
        $data     = $response->get_content();
        $this->assertTrue( $data->error );
        $this->assertSame( 'Error in club update', $data->msg );
        $this->assertSame( $data->status, $response->get_status_code() );
    }

    public function test_club_player_remove_rejects_invalid_nonce(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $request = array( 'racketmanager_nonce' => 'bad', 'clubPlayer' => array( 3 ) );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->registration_service->expects( $this->never() )->method( 'remove_registration' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->club_player_remove( $request );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertTrue( $response->get_content()->error );
    }

    public function test_missing_nonce_uses_problem_message_and_present_invalid_uses_expired_message(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $missing = $this->adapter->club_player_remove( array() )->get_content();
        $this->assertStringContainsString( 'problem with your request', $missing->msg );

        $empty = $this->adapter->club_player_remove( array( 'racketmanager_nonce' => '' ) )->get_content();
        $this->assertStringContainsString( 'expired or is invalid', $empty->msg );
    }

    public function test_show_club_role_modal_success_returns_raw_html(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = array( 'security' => 'valid', 'clubRoleId' => 9, 'modal' => 'roleModal' );

        $response = $this->adapter->show_club_role_modal( $request );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'club-role-modal:9:roleModal', $response->get_content() );
    }

    public function test_show_club_role_modal_nonce_failure_returns_alert_html_with_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $request = array( 'security' => 'bad' );

        $response = $this->adapter->show_club_role_modal( $request );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
        $this->assertStringContainsString( 'alert', $response->get_content() );
    }

    public function test_set_club_role_rejects_invalid_nonce(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $request = $this->role_post();
        $request['racketmanager_nonce'] = 'bad';
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'reassign_role_user' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->set_club_role( $request );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_set_club_role_validation_error_uses_fallback_message(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = $this->role_post();
        unset( $request['clubRoleId'] );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'reassign_role_user' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->set_club_role( $request );
        $this->assertSame( 'Unable to set club role', $response->get_content()->msg );
        $this->assertTrue( $response->get_content()->error );
    }

    public function test_set_club_role_reassigns_when_user_changes(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = $this->role_post();
        $this->stub_role_user( false );
        $GLOBALS['wp_stubs_club_roles'][9] = (object) array( 'user' => (object) array( 'id' => 99 ) );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->once() )->method( 'reassign_role_user' )->with( 9, 5 )->willReturn( $this->createStub( \Racketmanager\Domain\Club\Club_Role::class ) );
        $this->adapter = $this->make_adapter();

        $content = $this->adapter->set_club_role( $request )->get_content();
        $this->assertSame( 'success', $content->status );
        $this->assertSame( 'Club role updated', $content->msg );
    }

    public function test_set_club_role_same_user_skips_reassign_and_success_when_contact_updated(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = $this->role_post();
        $this->stub_role_user( true );
        $GLOBALS['wp_stubs_club_roles'][9] = (object) array( 'user' => (object) array( 'id' => 5 ) );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'reassign_role_user' );
        $this->adapter = $this->make_adapter();

        $content = $this->adapter->set_club_role( $request )->get_content();
        $this->assertSame( 'success', $content->status );
    }

    public function test_set_club_role_warns_when_nothing_changed(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $request = $this->role_post();
        $this->stub_role_user( false );
        $GLOBALS['wp_stubs_club_roles'][9] = (object) array( 'user' => (object) array( 'id' => 5 ) );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->club_service->expects( $this->never() )->method( 'reassign_role_user' );
        $this->adapter = $this->make_adapter();

        $content = $this->adapter->set_club_role( $request )->get_content();
        $this->assertSame( 'warning', $content->status );
        $this->assertSame( 'No updates', $content->msg );
    }
}
