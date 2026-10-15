<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Tournament_Service;
use WP_Error;

class Tournament_Ajax_Adapter_Test extends TestCase {
    private Stub|Security_Service_Interface $security_service;
    private Competition_Entry_Service|MockObject|Stub $competition_entry_service;
    private Finance_Service|MockObject|Stub $finance_service;
    private Tournament_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service          = $this->createStub( Security_Service_Interface::class );
        $this->competition_entry_service = $this->createStub( Competition_Entry_Service::class );
        $this->finance_service           = $this->createStub( Finance_Service::class );
        $this->adapter                   = $this->make_adapter();
        $post                           = array();
        $post                        = array();
        $GLOBALS['wp_stubs_get_current_user_id'] = 1;
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_get_current_user_id'],
            $GLOBALS['wp_stubs_current_user_id'],
            $GLOBALS['racketmanager_event_partner_modal_callback']
        );
        parent::tearDown();
    }

    private function make_adapter( ?Tournament_Service $tournament_service = null ): Tournament_Ajax_Adapter {
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

        return new Tournament_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $this->competition_entry_service,
            $this->finance_service,
            $tournament_service
        );
    }

    public function test_register_hooks_uses_single_priv_and_nopriv_ownership(): void {
        $controller = new Tournament_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = array();
        $controller->register_hooks( $registry );

        $this->assertSame( array( $controller, 'tournament_entry_request' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_tournament_entry'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_tournament_entry'][0] );
        $this->assertSame( array( $controller, 'tournament_payment_create' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_tournament_payment_create'][0] );
        $this->assertSame( array( $controller, 'logged_out' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_tournament_payment_create'][0] );
        $this->assertSame( array( $controller, 'tournament_withdrawal' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_tournament_withdrawal'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_tournament_withdrawal'][0] );
        $this->assertSame( array( $controller, 'confirm_tournament_withdrawal' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_confirm_tournament_withdrawal'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_confirm_tournament_withdrawal'][0] );
        $this->assertSame( array( $controller, 'team_partner' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_team_partner'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_team_partner'][0] );
        $this->assertSame( array( $controller, 'validate_partner' ), $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_validate_partner'][0] );
        $this->assertSame( array( $controller, 'logged_out_modal' ), $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_validate_partner'][0] );
        $this->assertCount( 1, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_tournament_entry'] );
    }

    public function test_tournament_entry_rejects_invalid_nonce(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $post['racketmanager_nonce']    = 'bad';

        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->never() )->method( 'request_tournament_entry' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->tournament_entry_request( $post ?? [] );
        $data     = $response->get_content();
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'expired or is invalid', $data->msg );
    }

    public function test_tournament_entry_success_maps_request_dto(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $tournament = $this->createStub( Tournament::class );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $post = array(
            'racketmanager_nonce' => 'valid',
            'tournament_id'       => 8,
            'playerId'            => 3,
            'clubId'              => 2,
            'season'              => '2026',
        );
        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->once() )
            ->method( 'request_tournament_entry' )
            ->with( $this->callback( static fn( $request ): bool =>
                $request instanceof Tournament_Entry_Request_DTO
                && 8 === $request->tournament_id
                && 3 === $request->player_id
                && 2 === $request->club_id
                && '2026' === $request->season
            ) )
            ->willReturn( new Tournament_Entry_Response_DTO( 200, 'Entered', 'success', '/pay', true ) );
        $this->adapter = $this->make_adapter( $tournament_service );

        $response = $this->adapter->tournament_entry_request( $post ?? [] );
        $data     = $response->get_content();
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Entered', $data[0] );
        $this->assertSame( 'success', $data[1] );
        $this->assertTrue( $data[2] );
        $this->assertSame( '/pay', $data[3] );
    }

    public function test_tournament_entry_missing_nonce_returns_request_error(): void {
        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->never() )->method( 'request_tournament_entry' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->tournament_entry_request( array() );

        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'problem with your request', $response->get_content()->msg );
    }

    public function test_tournament_entry_wp_error(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $tournament = $this->createStub( Tournament::class );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $post    = array( 'racketmanager_nonce' => 'valid', 'tournament_id' => 8 );
        $error = new WP_Error( 'event', 'Pick an event' );
        $this->competition_entry_service = $this->createStub( Competition_Entry_Service::class );
        $this->competition_entry_service->method( 'request_tournament_entry' )->willReturn( $error );
        $this->adapter = $this->make_adapter( $tournament_service );

        $response = $this->adapter->tournament_entry_request( $post ?? [] );
        $data     = $response->get_content();
        $this->assertSame( 400, $response->get_status_code() );
        $this->assertSame( 'Errors in tournament entry form', $data->msg );
        $this->assertContains( 'event', $data->err_flds );
    }

    public function test_validate_partner_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post = array(
            'racketmanager_nonce' => 'valid',
            'modal'               => 'partner-modal',
            'partnerId'           => 15,
            'partner'             => 'Jane Doe',
            'eventId'             => 4,
        );
        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->once() )
            ->method( 'validate_tournament_partner' )
            ->with( $this->isInstanceOf( Tournament_Partner_Request_DTO::class ) )
            ->willReturn( true );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_partner( $post ?? [] );
        $data     = $response->get_content();
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'partner-modal', $data[0] );
        $this->assertSame( 15, $data[1] );
        $this->assertSame( 'Jane Doe', $data[2] );
        $this->assertSame( 4, $data[3] );
    }

    public function test_confirm_withdrawal_success_with_refund(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post    = array( 'security' => 'valid', 'tournament_id' => 8, 'playerId' => 3 );
        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->once() )
            ->method( 'confirm_tournament_withdrawal' )
            ->with( 8, 3 )
            ->willReturn( 1 );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->confirm_tournament_withdrawal( $post ?? [] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringContainsString( 'refund will be issued', $response->get_content() );
    }

    public function test_confirm_withdrawal_not_found(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post    = array( 'security' => 'valid', 'tournament_id' => 8, 'playerId' => 3 );
        $this->competition_entry_service = $this->createStub( Competition_Entry_Service::class );
        $this->competition_entry_service->method( 'confirm_tournament_withdrawal' )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Missing tournament' ) );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->confirm_tournament_withdrawal( $post ?? [] );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertContains( 'Missing tournament', $response->get_content()->err_msgs );
    }

    public function test_payment_create_returns_client_secret(): void {
        $post = array( 'invoiceId' => 11, 'tournament_id' => 8, 'playerId' => 3 );
        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->finance_service->expects( $this->once() )
            ->method( 'create_tournament_payment_request' )
            ->with( 8, 3, 11 )
            ->willReturn( 'pi_test_secret' );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->tournament_payment_create( $post ?? [] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'pi_test_secret', $response->get_content() );
    }

    public function test_payment_create_maps_stripe_error(): void {
        $post = array( 'invoiceId' => 11, 'tournament_id' => 8, 'playerId' => 3 );
        $this->finance_service = $this->createStub( Finance_Service::class );
        $this->finance_service->method( 'create_tournament_payment_request' )
            ->willThrowException( new Stripe_API_Exception( 'card declined' ) );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->tournament_payment_create( $post ?? [] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'card declined', $response->get_content()['error'] );
    }

    public function test_logged_out_modal_payload(): void {
        $response = $this->adapter->logged_out_modal();
        $data     = $response->get_content();
        $this->assertSame( 401, $response->get_status_code() );
        $this->assertStringContainsString( 'Must be logged in', $data[0] );
        $this->assertStringContainsString( 'alert', $data[1] );
    }

    public function test_tournament_withdrawal_nonce_failure_is_raw_message_with_status_200(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $post    = array( 'security' => 'bad', 'tournamentId' => 8 );

        $response = $this->adapter->tournament_withdrawal( $post ?? [] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
        $this->assertStringContainsString( 'expired or is invalid', $response->get_content() );
    }

    public function test_tournament_withdrawal_missing_nonce_message(): void {
        $response = $this->adapter->tournament_withdrawal( array() );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertIsString( $response->get_content() );
        $this->assertStringContainsString( 'problem with your request', $response->get_content() );
    }

    public function test_tournament_withdrawal_modal_success_returns_html(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post    = array( 'security' => 'ok', 'tournamentId' => 8, 'modal' => 'm' );

        $response = $this->adapter->tournament_withdrawal( $post ?? [] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertStringStartsWith( 'tournament-withdrawal-modal:', $response->get_content() );
    }

    public function test_team_partner_success_returns_html(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post    = array( 'security' => 'ok', 'eventId' => 4 );

        $response = $this->adapter->team_partner( $post ?? [] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'event-partner-modal:4', $response->get_content() );
    }

    public function test_team_partner_nonce_failure_is_alert_html_with_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $post    = array( 'security' => 'bad' );

        $response = $this->adapter->team_partner( $post ?? [] );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'alert', $response->get_content() );
    }

    public function test_validate_partner_preserves_wp_error_status(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $post = [
            'racketmanager_nonce' => 'valid',
            'partner'             => 'Jane Doe',
            'partnerId'           => 15,
            'eventId'             => 4,
        ];

        $wp_error = new WP_Error( 'partner', 'Partner is not eligible', [ 'status' => 422 ] );
        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->once() )
            ->method( 'validate_tournament_partner' )
            ->willReturn( $wp_error );
        $this->adapter = $this->make_adapter();

        $response = $this->adapter->validate_partner( $post );
        $this->assertSame( 422, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->error );
        $this->assertSame( 422, $content->status );
    }

    public function test_team_partner_sanitizes_malicious_season(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $received_args = null;
        $GLOBALS['racketmanager_event_partner_modal_callback'] = function ( $event_id, $args ) use ( &$received_args ) {
            $received_args = $args;
            return 'modal-content';
        };

        $post = [
            'security' => 'ok',
            'eventId'  => 4,
            'season'   => '<script>alert(1)</script>',
        ];

        $response = $this->adapter->team_partner( $post );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertNotNull( $received_args );
        $this->assertNull( $received_args['season'] );
    }

    public function test_tournament_entry_request_rejects_disallowed_event_and_does_not_call_service(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $calls = [];
        $tournament = $this->createMock( Tournament::class );
        $tournament->expects( $this->exactly( 2 ) )
            ->method( 'is_event_allowed' )
            ->willReturnCallback( static function ( $event_id, $for_entry ) use ( &$calls ): bool {
                $calls[] = [ $event_id, $for_entry ];
                return 42 === $event_id;
            } );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->expects( $this->once() )
            ->method( 'get_tournament' )
            ->with( 8 )
            ->willReturn( $tournament );

        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->never() )
            ->method( 'request_tournament_entry' );

        $adapter = $this->make_adapter( $tournament_service );

        $post = [
            'racketmanager_nonce' => 'valid',
            'tournament_id'       => 8,
            'tournamentId'        => 999,
            'playerId'            => 3,
            'clubId'              => 1,
            'season'              => '2026',
            'event'               => [ 42, 99 ],
            'eventFee'            => [ 42 => '10.00', 99 => '10.00' ],
        ];

        $response = $adapter->tournament_entry_request( $post );
        $this->assertSame( 422, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertTrue( $content->error );
        $this->assertSame( 422, $content->status );
        $this->assertSame( [ 'event' ], $content->err_flds );
        $this->assertSame( [ 'Event is not open for entry in this tournament' ], $content->err_msgs );
        $this->assertSame( [ [ 42, true ], [ 99, true ] ], $calls );
    }

    public function test_tournament_entry_request_allows_valid_event_and_calls_service(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $tournament = $this->createMock( Tournament::class );
        $tournament->expects( $this->once() )
            ->method( 'is_event_allowed' )
            ->with( 42, true )
            ->willReturn( true );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->expects( $this->once() )
            ->method( 'get_tournament' )
            ->with( 8 )
            ->willReturn( $tournament );

        $entry_response = new Tournament_Entry_Response_DTO(
            200,
            'Entry successful',
            'success',
            '/tournament/8/',
            false
        );

        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->once() )
            ->method( 'request_tournament_entry' )
            ->with( $this->callback( static fn( Tournament_Entry_Request_DTO $dto ): bool =>
                8 === $dto->tournament_id && [ 42 ] === array_keys( $dto->entries )
            ) )
            ->willReturn( $entry_response );

        $adapter = $this->make_adapter( $tournament_service );

        $post = [
            'racketmanager_nonce' => 'valid',
            'tournament_id'       => 8,
            'tournamentId'        => 999,
            'playerId'            => 3,
            'clubId'              => 1,
            'season'              => '2026',
            'event'               => [ 42 ],
            'eventFee'            => [ 42 => '10.00' ],
        ];

        $response = $adapter->tournament_entry_request( $post );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Entry successful', $content[0] );
    }

    public function test_tournament_entry_request_rejects_unresolved_tournament(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->expects( $this->once() )
            ->method( 'get_tournament' )
            ->with( 8 )
            ->willReturn( null );

        $this->competition_entry_service = $this->createMock( Competition_Entry_Service::class );
        $this->competition_entry_service->expects( $this->never() )
            ->method( 'request_tournament_entry' );

        $adapter = $this->make_adapter( $tournament_service );
        $response = $adapter->tournament_entry_request( [
            'racketmanager_nonce' => 'valid',
            'tournament_id'       => 8,
            'playerId'            => 3,
            'event'               => [ 42 ],
            'eventFee'            => [ 42 => '10.00' ],
        ] );

        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( [ 'tournament' ], $response->get_content()->err_flds );
    }

    public function test_team_partner_sanitizes_wrong_tournament_season_to_null(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )
            ->willReturn( $tournament );

        $adapter = $this->make_adapter( $tournament_service );

        $received_args = null;
        $GLOBALS['racketmanager_event_partner_modal_callback'] = function ( $event_id, $args ) use ( &$received_args ) {
            $received_args = $args;
            return 'modal-content';
        };

        $post = [
            'security'      => 'ok',
            'tournament_id' => 8,
            'eventId'       => 4,
            'season'        => '2024', // Format-valid but wrong season for 2026 tournament
        ];

        $response = $adapter->team_partner( $post );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertNotNull( $received_args );
        $this->assertNull( $received_args['season'] );
    }

    public function test_team_partner_preserves_matching_tournament_season(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )
            ->willReturn( $tournament );

        $adapter = $this->make_adapter( $tournament_service );

        $received_args = null;
        $GLOBALS['racketmanager_event_partner_modal_callback'] = function ( $event_id, $args ) use ( &$received_args ) {
            $received_args = $args;
            return 'modal-content';
        };

        $post = [
            'security'      => 'ok',
            'tournament_id' => 8,
            'eventId'       => 4,
            'season'        => '2026',
        ];

        $response = $adapter->team_partner( $post );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertNotNull( $received_args );
        $this->assertSame( '2026', $received_args['season'] );
    }
}
