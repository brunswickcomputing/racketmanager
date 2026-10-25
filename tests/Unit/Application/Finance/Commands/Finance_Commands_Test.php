<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Finance\Commands;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Command;
use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Command;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler;
use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Command;
use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Handler;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finance_Commands_Test extends TestCase {

	public function test_set_purchase_order_command_from_post_and_constructor(): void {
		$post = [
			'invoiceId'     => '105',
			'purchaseOrder' => 'PO-2026-ABC',
			'modal'         => 'po-dialog',
		];

		$command = Set_Purchase_Order_Command::from_post( $post );

		$this->assertSame( 105, $command->invoice_id );
		$this->assertSame( 'PO-2026-ABC', $command->purchase_order );
		$this->assertSame( 'po-dialog', $command->modal );

		$empty_command = Set_Purchase_Order_Command::from_post( [] );
		$this->assertNull( $empty_command->invoice_id );
		$this->assertNull( $empty_command->purchase_order );
		$this->assertNull( $empty_command->modal );
	}

	public function test_set_purchase_order_handler_delegates_to_service(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'set_purchase_order_for_invoice' )
			->with( 105, 'PO-2026-ABC' )
			->willReturn( true );

		$handler = new Set_Purchase_Order_Handler( $service );
		$command = new Set_Purchase_Order_Command( 105, 'PO-2026-ABC', 'po-dialog' );

		$result = $handler->handle( $command );
		$this->assertTrue( $result );
	}

	public function test_set_purchase_order_handler_propagates_exceptions(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'set_purchase_order_for_invoice' )
			->with( 999, 'PO-FAIL' )
			->willThrowException( new Invoice_Not_Found_Exception( 'Invoice not found' ) );

		$handler = new Set_Purchase_Order_Handler( $service );
		$command = new Set_Purchase_Order_Command( 999, 'PO-FAIL', 'po-dialog' );

		$this->expectException( Invoice_Not_Found_Exception::class );
		$this->expectExceptionMessage( 'Invoice not found' );

		$handler->handle( $command );
	}

	public function test_generate_charge_invoices_command_from_post_variations(): void {
		$post1 = [
			'chargesId'  => '22',
			'send_email' => '1',
		];
		$cmd1 = Generate_Charge_Invoices_Command::from_post( $post1 );
		$this->assertSame( 22, $cmd1->charge_id );
		$this->assertTrue( $cmd1->send_email );

		$post2 = [
			'charges_id' => '33',
			'send_email' => '0',
		];
		$cmd2 = Generate_Charge_Invoices_Command::from_post( $post2 );
		$this->assertSame( 33, $cmd2->charge_id );
		$this->assertFalse( $cmd2->send_email );

		$post3 = [
			'charge_id' => '44',
			'send'      => 'false',
		];
		$cmd3 = Generate_Charge_Invoices_Command::from_post( $post3 );
		$this->assertSame( 44, $cmd3->charge_id );
		$this->assertFalse( $cmd3->send_email );

		$post4 = [
			'chargesId' => '55',
			'send'      => 'yes',
		];
		$cmd4 = Generate_Charge_Invoices_Command::from_post( $post4 );
		$this->assertSame( 55, $cmd4->charge_id );
		$this->assertTrue( $cmd4->send_email );

		$empty_cmd = Generate_Charge_Invoices_Command::from_post( [] );
		$this->assertSame( 0, $empty_cmd->charge_id );
		$this->assertTrue( $empty_cmd->send_email );
	}

	public function test_generate_charge_invoices_handler_delegates_to_service(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'send_invoices_for_charge' )
			->with( 22, true )
			->willReturn( true );

		$handler = new Generate_Charge_Invoices_Handler( $service );
		$command = new Generate_Charge_Invoices_Command( 22, true );

		$result = $handler->handle( $command );
		$this->assertTrue( $result );
	}

	public function test_generate_charge_invoices_handler_propagates_exceptions(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'send_invoices_for_charge' )
			->with( 99, false )
			->willThrowException( new Charge_Not_Found_Exception( 'Charge not found' ) );

		$handler = new Generate_Charge_Invoices_Handler( $service );
		$command = new Generate_Charge_Invoices_Command( 99, false );

		$this->expectException( Charge_Not_Found_Exception::class );
		$this->expectExceptionMessage( 'Charge not found' );

		$handler->handle( $command );
	}

	public function test_action_invoice_command_factory_and_constructor(): void {
		$command = Action_Invoice_Command::create( 50, '  paid  ' );
		$this->assertSame( 50, $command->invoice_id );
		$this->assertSame( 'paid', $command->action );
	}

	public function test_action_invoice_handler_delegates_to_service(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'action_invoice' )
			->with( 50, 'paid' )
			->willReturn( 'Invoice #50 marked as paid' );

		$handler = new Action_Invoice_Handler( $service );
		$command = new Action_Invoice_Command( 50, 'paid' );

		$result = $handler->handle( $command );
		$this->assertSame( 'Invoice #50 marked as paid', $result );
	}

	public function test_action_invoice_handler_propagates_exceptions(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'action_invoice' )
			->with( 50, 'invalid' )
			->willThrowException( new Invalid_Argument_Exception( 'Invalid action' ) );

		$handler = new Action_Invoice_Handler( $service );
		$command = new Action_Invoice_Command( 50, 'invalid' );

		$this->expectException( Invalid_Argument_Exception::class );
		$this->expectExceptionMessage( 'Invalid action' );

		$handler->handle( $command );
	}
}
