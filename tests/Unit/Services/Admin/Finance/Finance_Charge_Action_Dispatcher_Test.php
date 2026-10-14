<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Finance;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Charge;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Charge_Not_Deleted_Exception;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Charge_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Finance_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finance_Charge_Action_Dispatcher_Test extends TestCase {

    private Finance_Service $finance_service;
    private Action_Guard_Interface $guard;
    private Finance_Charge_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->finance_service = $this->createMock( Finance_Service::class );
        $this->guard           = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher      = new Finance_Charge_Action_Dispatcher( $this->finance_service, $this->guard );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );
        self::assertNull( $result->message );
    }

    public function test_delete_charges_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'del_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Guard rejected' ) );

        $this->finance_service->expects( self::never() )->method( 'remove_charge' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array( 5 ),
            )
        );

        self::assertSame( 'Guard rejected', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'charges' ), $result->query_params );
    }

    public function test_delete_charges_empty_charges_warning(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::never() )->method( 'remove_charge' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array(),
            )
        );

        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_delete_charges_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'remove_charge' )
            ->with( 10 )
            ->willReturn( 1 );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array( '10' ),
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_charges_return_false_gives_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'remove_charge' )
            ->with( 10 )
            ->willReturn( 0 );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array( 10 ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_charges_mixed_results_gives_error_message(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::exactly( 2 ) )
            ->method( 'remove_charge' )
            ->willReturnCallback(
                function( int $id ) {
                    if ( 1 === $id ) {
                        return 1;
                    }
                    throw new Charge_Not_Found_Exception( 'Charge 2 not found' );
                }
            );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'doChargesDel' => '1',
                'action'       => 'delete',
                'charge'       => array( 1, 2 ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Charge 2 not found', $result->message );
    }

    public function test_generate_invoices_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_charges-bulk', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->finance_service->expects( self::never() )->method( 'send_invoices_for_charge' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'generateInvoices' => '1',
                'chargesId'        => '5',
            )
        );

        self::assertSame( 'Permission denied', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_generate_invoices_missing_charge_id(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::never() )->method( 'send_invoices_for_charge' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'generateInvoices' => '1',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'charges' ), $result->query_params );
    }

    public function test_generate_invoices_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'send_invoices_for_charge' )
            ->with( 15, true )
            ->willReturn( true );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'generateInvoices' => '1',
                'chargesId'        => '15',
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'view' => 'charge', 'charges' => 15 ), $result->query_params );
    }

    public function test_generate_invoices_failure(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'send_invoices_for_charge' )
            ->with( 15, true )
            ->willReturn( false );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'generateInvoices' => '1',
                'charges_id'       => '15',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'charge', 'charges' => 15 ), $result->query_params );
    }

    public function test_generate_invoices_exception(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->finance_service->expects( self::once() )
            ->method( 'send_invoices_for_charge' )
            ->willThrowException( new Invoice_Not_Created_Exception( 'Invoice creation failed' ) );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'generateInvoices' => '1',
                'chargesId'        => '15',
            )
        );

        self::assertSame( 'Invoice creation failed', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_charge_guard_failure(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-charges', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid nonce' ) );

        $this->finance_service->expects( self::never() )->method( 'add_charge' );
        $this->finance_service->expects( self::never() )->method( 'amend_charge' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveCharge' => '1',
                'addCharge'  => '1',
            )
        );

        self::assertSame( 'Invalid nonce', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_charge_success_redirects_to_new_id(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $charge = new Charge();
        $charge->set_id( 99 );

        $this->finance_service->expects( self::once() )
            ->method( 'add_charge' )
            ->willReturn( $charge );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveCharge'     => '1',
                'addCharge'      => '1',
                'competition_id' => '2',
                'season'         => '2026',
                'status'         => 'draft',
                'date'           => '2026-05-01',
                'feeClub'        => '25.50',
                'feeTeam'        => '10.00',
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'view' => 'charge', 'charges' => 99 ), $result->query_params );
    }

    public function test_add_charge_wp_error_flashes_errors_and_sanitized_form_data(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_error = new WP_Error( 'competition', 'Competition is required' );

        $this->finance_service->expects( self::once() )
            ->method( 'add_charge' )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveCharge'     => '1',
                'addCharge'      => '1',
                'competition_id' => '',
                'season'         => '2026',
                'status'         => 'draft',
                'feeClub'        => '25.00',
                'unwanted'       => 'ignored',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'charge' ), $result->query_params );
        self::assertArrayHasKey( 'competition', $result->flash_data['errors'] );
        self::assertSame( '2026', $result->flash_data['form_data']['season'] );
        self::assertArrayNotHasKey( 'unwanted', $result->flash_data['form_data'] );
    }

    public function test_edit_charge_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $charge = new Charge();
        $charge->set_id( 12 );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->with( self::isInstanceOf( \stdClass::class ), 12 )
            ->willReturn( $charge );

        $result = $this->dispatcher->handle(
            array( 'charges' => '12' ),
            array(
                'saveCharge' => '1',
                'editCharge' => '1',
                'charges_id' => '12',
                'feeClub'    => '30.00',
                'feeTeam'    => '15.00',
            )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'view' => 'charge', 'charges' => 12 ), $result->query_params );
    }

    public function test_edit_charge_wp_error_flashes_errors(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_error = new WP_Error( 'date', 'Invalid date format' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array( 'charges' => '12' ),
            array(
                'saveCharge' => '1',
                'editCharge' => '1',
                'charges_id' => '12',
                'date'       => 'invalid',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertArrayHasKey( 'date', $result->flash_data['errors'] );
    }

    public function test_edit_charge_not_updated_warning(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->willThrowException( new Charge_Not_Updated_Exception( 'No changes made' ) );

        $result = $this->dispatcher->handle(
            array( 'charges' => '12' ),
            array(
                'saveCharge' => '1',
                'editCharge' => '1',
                'charges_id' => '12',
            )
        );

        self::assertSame( 'No changes made', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_edit_charge_not_found_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->finance_service->expects( self::once() )
            ->method( 'amend_charge' )
            ->willThrowException( new Charge_Not_Found_Exception( 'Charge not found' ) );

        $result = $this->dispatcher->handle(
            array( 'charges' => '12' ),
            array(
                'saveCharge' => '1',
                'editCharge' => '1',
                'charges_id' => '12',
            )
        );

        self::assertSame( 'Charge not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_charge_without_add_or_edit_flag_returns_invalid_action(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array(),
            array(
                'saveCharge' => '1',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( array( 'view' => 'charges' ), $result->query_params );
    }
}
