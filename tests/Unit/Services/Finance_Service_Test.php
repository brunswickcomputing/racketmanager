<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Gateways\Payment\Payment_Gateway_Interface;
use Racketmanager\Gateways\Payment\Payment_Intent_Result;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use WP_Error;

class Finance_Service_Test extends TestCase {

    private RacketManager $app;
    private Charge_Repository_Interface $charge_repo;
    private Invoice_Repository_Interface $invoice_repo;
    private Club_Repository_Interface $club_repo;
    private Tournament_Repository_Interface $tournament_repo;
    private Competition_Service $competition_service;
    private Player_Service $player_service;

    protected function setUp(): void {
        parent::setUp();
        $this->app                 = $this->createStub( RacketManager::class );
        $this->app->currency_code  = 'GBP';
        $this->charge_repo         = $this->createStub( Charge_Repository_Interface::class );
        $this->invoice_repo        = $this->createStub( Invoice_Repository_Interface::class );
        $this->club_repo           = $this->createStub( Club_Repository_Interface::class );
        $this->tournament_repo     = $this->createStub( Tournament_Repository_Interface::class );
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->player_service      = $this->createStub( Player_Service::class );
    }

    public function test_construct_fallback_initializes_default_gateways_without_type_error(): void {
        $this->app->method( 'get_options' )->willReturn( [
            'stripe_is_live'      => false,
            'api_secret_key_test' => 'sk_test_fallback',
        ] );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service
        );

        $this->assertInstanceOf( Finance_Service::class, $service );
    }

    public function test_create_tournament_payment_request_success(): void {
        $payment_gateway = $this->createMock( Payment_Gateway_Interface::class );
        $invoice_repo    = $this->createMock( Invoice_Repository_Interface::class );
        $tournament_repo = $this->createMock( Tournament_Repository_Interface::class );
        $player_service  = $this->createMock( Player_Service::class );
        $mailer          = $this->createStub( Mailer_Interface::class );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $tournament_repo,
            $this->competition_service,
            $player_service,
            $payment_gateway,
            $mailer
        );

        $invoice = new Invoice();
        $invoice->id = 101;
        $invoice->amount = '25.00';

        $invoice_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 101 )
            ->willReturn( $invoice );

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'County Championship' );

        $tournament_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 202 )
            ->willReturn( $tournament );

        $player = $this->createStub( Player::class );
        $player->method( 'get_fullname' )->willReturn( 'Jane Doe' );
        $player->method( 'get_email' )->willReturn( 'jane.doe@example.com' );

        $player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 303 )
            ->willReturn( $player );

        $payment_gateway->expects( self::once() )
            ->method( 'create_payment_intent' )
            ->with( self::callback( function( array $args ) {
                return $args['amount'] === 2500
                    && $args['currency'] === 'GBP'
                    && $args['description'] === 'Jane Doe - County Championship'
                    && $args['statement_descriptor_suffix'] === 'County Championship'
                    && $args['receipt_email'] === 'jane.doe@example.com'
                    && $args['payment_method_types'] === [ 'card' ];
            } ) )
            ->willReturn( new Payment_Intent_Result(
                id: 'pi_test_intent_789',
                client_secret: 'pi_test_intent_789_secret_xyz',
                status: 'requires_payment_method',
                amount: 2500,
                currency: 'GBP'
            ) );

        $invoice_repo->expects( self::once() )
            ->method( 'save' )
            ->with( self::callback( function( Invoice $inv ) {
                return $inv->get_payment_reference() === 'pi_test_intent_789';
            } ) )
            ->willReturn( true );

        $client_secret = $service->create_tournament_payment_request( 202, 303, 101 );

        $this->assertSame( 'pi_test_intent_789_secret_xyz', $client_secret );
        $this->assertSame( 'pi_test_intent_789', $invoice->get_payment_reference() );
    }

    public function test_create_tournament_payment_request_throws_when_invoice_not_found(): void {
        $payment_gateway = $this->createStub( Payment_Gateway_Interface::class );
        $invoice_repo    = $this->createMock( Invoice_Repository_Interface::class );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $payment_gateway
        );

        $invoice_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $this->expectException( Invoice_Not_Found_Exception::class );
        $service->create_tournament_payment_request( 202, 303, 999 );
    }

    public function test_create_tournament_payment_request_throws_when_tournament_not_found(): void {
        $payment_gateway = $this->createStub( Payment_Gateway_Interface::class );
        $tournament_repo = $this->createMock( Tournament_Repository_Interface::class );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $tournament_repo,
            $this->competition_service,
            $this->player_service,
            $payment_gateway
        );

        $invoice = new Invoice();
        $this->invoice_repo->method( 'find_by_id' )->willReturn( $invoice );

        $tournament_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 888 )
            ->willReturn( null );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $service->create_tournament_payment_request( 888, 303, 101 );
    }

    public function test_create_tournament_payment_request_throws_when_player_not_found(): void {
        $payment_gateway = $this->createStub( Payment_Gateway_Interface::class );
        $player_service  = $this->createMock( Player_Service::class );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $player_service,
            $payment_gateway
        );

        $invoice = new Invoice();
        $this->invoice_repo->method( 'find_by_id' )->willReturn( $invoice );

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'Open' );
        $this->tournament_repo->method( 'find_by_id' )->willReturn( $tournament );

        $player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 777 )
            ->willThrowException( new Player_Not_Found_Exception( 'Player 777 not found' ) );

        $this->expectException( Player_Not_Found_Exception::class );
        $service->create_tournament_payment_request( 202, 777, 101 );
    }

    public function test_create_tournament_payment_request_propagates_gateway_exception(): void {
        $payment_gateway = $this->createMock( Payment_Gateway_Interface::class );
        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $payment_gateway
        );

        $invoice = new Invoice();
        $invoice->id = 101;
        $invoice->amount = '25.00';
        $this->invoice_repo->method( 'find_by_id' )->willReturn( $invoice );

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'Open' );
        $this->tournament_repo->method( 'find_by_id' )->willReturn( $tournament );

        $player = $this->createStub( Player::class );
        $player->method( 'get_fullname' )->willReturn( 'Jane' );
        $player->method( 'get_email' )->willReturn( 'jane@example.com' );
        $this->player_service->method( 'get_player' )->willReturn( $player );

        $payment_gateway->expects( self::once() )
            ->method( 'create_payment_intent' )
            ->willThrowException( new Stripe_API_Exception( 'Card was declined' ) );

        $this->expectException( Stripe_API_Exception::class );
        $this->expectExceptionMessage( 'Card was declined' );

        $service->create_tournament_payment_request( 202, 303, 101 );
    }

    public function test_create_tournament_payment_request_uses_eur_billing_currency(): void {
        $app = $this->createStub( RacketManager::class );
        $app->currency_code = 'GBP';
        $app->method( 'get_options' )->willReturnCallback(
            static fn( $key = null ) => 'billing' === $key ? [ 'billingCurrency' => 'EUR' ] : []
        );

        $payment_gateway = $this->createMock( Payment_Gateway_Interface::class );
        $invoice_repo    = $this->createStub( Invoice_Repository_Interface::class );
        $tournament_repo = $this->createStub( Tournament_Repository_Interface::class );
        $player_service  = $this->createStub( Player_Service::class );

        $service = new Finance_Service(
            $app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $tournament_repo,
            $this->competition_service,
            $player_service,
            $payment_gateway,
            $this->createStub( Mailer_Interface::class )
        );

        $invoice         = new Invoice();
        $invoice->id     = 101;
        $invoice->amount = '19.99';
        $invoice_repo->method( 'find_by_id' )->willReturn( $invoice );

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'Open');
        $tournament_repo->method( 'find_by_id' )->willReturn( $tournament );

        $player = $this->createStub( Player::class );
        $player->method( 'get_fullname' )->willReturn( 'Jane Doe' );
        $player->method( 'get_email' )->willReturn( 'jane.doe@example.com' );
        $player_service->method( 'get_player' )->willReturn( $player );

        $payment_gateway->expects( self::once() )
            ->method( 'create_payment_intent' )
            ->with( self::callback( static function ( array $args ) {
                return 1999 === $args['amount'] && 'GBP' === $args['currency'];
            } ) )
            ->willReturn( new Payment_Intent_Result(
                id: 'pi_eur',
                client_secret: 'secret',
                status: 'requires_payment_method',
                amount: 1999,
                currency: 'GBP'
            ) );

        $invoice_repo->method( 'save' )->willReturn( true );

        self::assertSame( 'secret', $service->create_tournament_payment_request( 202, 303, 101 ) );
    }

    public function test_create_tournament_payment_request_uses_unsupported_locale_currency(): void {
        $app = $this->createStub( RacketManager::class );
        $app->currency_code = 'AUD';
        $app->method( 'get_options' )->willReturn([]); // No billing currency set

        $payment_gateway = $this->createMock( Payment_Gateway_Interface::class );
        $invoice_repo    = $this->createStub( Invoice_Repository_Interface::class );
        $tournament_repo = $this->createStub( Tournament_Repository_Interface::class );
        $player_service  = $this->createStub( Player_Service::class );

        $service = new Finance_Service(
            $app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $tournament_repo,
            $this->competition_service,
            $player_service,
            $payment_gateway,
            $this->createStub( Mailer_Interface::class )
        );
        $invoice = new Invoice();
        $invoice->id = 101;
        $invoice->amount = '19.99';
        $invoice_repo->method( 'find_by_id' )->willReturn($invoice);

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'Open');
        $tournament_repo->method( 'find_by_id' )->willReturn( $tournament );

        $player = $this->createStub( Player::class );
        $player->method( 'get_fullname' )->willReturn( 'Jane Doe' );
        $player->method( 'get_email' )->willReturn( 'jane@example.com' );
        $player_service->method( 'get_player' )->willReturn( $player );
        
        $payment_gateway->expects( self::once() )
            ->method( 'create_payment_intent' )
            ->with( self::callback( static function ( array $args ) {
                return 'AUD' === $args['currency'];
            } ) )
            ->willReturn( new Payment_Intent_Result(
                id: 'pi_aud',
                client_secret: 'secret',
                status: 'requires_payment_method',
                amount: 1999,
                currency: 'AUD'
            ) );

        $service->create_tournament_payment_request( 202, 303, 101 );
    }

    public function test_get_currency_falls_back_when_billing_currency_missing(): void {
        $this->app->method( 'get_options' )->willReturn( [] );
        $this->app->currency_code = 'GBP';

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $this->createStub( Payment_Gateway_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        self::assertSame( Currency::GBP, $service->get_currency() );
    }

    public function test_get_currency_falls_back_when_billing_currency_empty(): void {
        $this->app->method( 'get_options' )->willReturn( [ 'billingCurrency' => '' ] );
        $this->app->currency_code = 'GBP';

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $this->invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $this->createStub( Payment_Gateway_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        self::assertSame( Currency::GBP, $service->get_currency() );
    }

    public function test_amend_invoice_normalizes_resend_to_sent(): void {
        $invoice_repo = $this->createMock( Invoice_Repository_Interface::class );
        $invoice      = new Invoice();
        $invoice->id  = 10;
        $invoice->set_status( 'draft' );

        $invoice_repo->method( 'find_by_id' )->willReturn( $invoice );
        $invoice_repo->expects( self::once() )->method( 'save' )->willReturn( true );

        $service = $this->getMockBuilder( Finance_Service::class )
            ->setConstructorArgs( [
                $this->app,
                $this->charge_repo,
                $invoice_repo,
                $this->club_repo,
                $this->tournament_repo,
                $this->competition_service,
                $this->player_service,
                $this->createStub( Payment_Gateway_Interface::class ),
                $this->createStub( Mailer_Interface::class ),
            ] )
            ->onlyMethods( [ 'send_invoice' ] )
            ->getMock();
        $service->expects( self::once() )->method( 'send_invoice' )->willReturn( true );

        $result = $service->amend_invoice( 10, 'resend', 'PO-1' );

        self::assertInstanceOf( Invoice::class, $result );
        self::assertSame( Invoice_Status::SENT, $invoice->get_status_enum() );
        self::assertSame( 'sent', $invoice->get_status() );
    }

    public function test_amend_invoice_rejects_invalid_status(): void {
        $invoice_repo = $this->createMock( Invoice_Repository_Interface::class );
        $invoice      = new Invoice();
        $invoice->id  = 10;
        $invoice->set_status( 'draft' );
        $invoice_repo->method( 'find_by_id' )->willReturn( $invoice );
        $invoice_repo->expects( self::never() )->method( 'save' );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $this->createStub( Payment_Gateway_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        $result = $service->amend_invoice( 10, 'bogus', 'PO-1' );

        self::assertInstanceOf( WP_Error::class, $result );
        self::assertSame( 'draft', $invoice->get_status() );
        self::assertStringContainsString( 'bogus', $result->get_error_message( 'status' ) );
    }

    public function test_action_invoice_rejects_new_and_sent_statuses(): void {
        $invoice_repo = $this->createMock( Invoice_Repository_Interface::class );
        $invoice      = new Invoice();
        $invoice->id  = 10;
        $invoice->set_status( 'draft' );
        $invoice_repo->method( 'find_by_id' )->willReturn( $invoice );
        $invoice_repo->expects( self::never() )->method( 'save' );

        $service = new Finance_Service(
            $this->app,
            $this->charge_repo,
            $invoice_repo,
            $this->club_repo,
            $this->tournament_repo,
            $this->competition_service,
            $this->player_service,
            $this->createStub( Payment_Gateway_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        $new_msg = $service->action_invoice( 10, 'new' );
        self::assertSame( 'draft', $invoice->get_status() );
        self::assertStringContainsString( 'new', $new_msg );

        $sent_msg = $service->action_invoice( 10, 'sent' );
        self::assertSame( 'draft', $invoice->get_status() );
        self::assertStringContainsString( 'sent', $sent_msg );
    }
}
