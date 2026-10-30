<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Finance\Queries;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\DTOs\Purchase_Order_Result;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Finance_Queries_Test extends TestCase {

	public function test_query_from_request_parsing(): void {
		$request = [
			'invoiceId' => '42',
			'modal'     => 'poModal',
			'template'  => 'custom',
		];

		$query = Get_Purchase_Order_Query::from_request( $request );

		$this->assertSame( 42, $query->id );
		$this->assertSame( 'poModal', $query->modal );
		$this->assertNull( $query->template );

		$alt_request = [
			'id'    => '77',
			'modal' => 'dialog',
		];
		$alt_query   = Get_Purchase_Order_Query::from_request( $alt_request );
		$this->assertSame( 77, $alt_query->id );
		$this->assertSame( 'dialog', $alt_query->modal );
		$this->assertNull( $alt_query->template );

		$empty_query = Get_Purchase_Order_Query::from_request( [] );
		$this->assertSame( 0, $empty_query->id );
		$this->assertNull( $empty_query->modal );
		$this->assertNull( $empty_query->template );
	}

	public function test_query_from_atts_parsing(): void {
		$atts = [
			'id'       => '55',
			'modal'    => 'poModal',
			'template' => 'edit',
		];

		$query = Get_Purchase_Order_Query::from_atts( $atts );

		$this->assertSame( 55, $query->id );
		$this->assertSame( 'poModal', $query->modal );
		$this->assertSame( 'edit', $query->template );

		$query_from_vars = Get_Purchase_Order_Query::from_atts( [], [ 'id' => '66' ] );
		$this->assertSame( 66, $query_from_vars->id );

		$query_from_get = Get_Purchase_Order_Query::from_atts( [], [], [ 'id' => '77' ] );
		$this->assertSame( 77, $query_from_get->id );
	}

	public function test_handler_success_returns_view_model_with_invoice(): void {
		$invoice = $this->createMock( Invoice::class );
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'get_invoice' )
			->with( 42 )
			->willReturn( $invoice );

		$handler = new Get_Purchase_Order_Handler( $service );
		$query   = new Get_Purchase_Order_Query( id: 42, modal: 'poModal', template: null );

		$result = $handler->handle( $query );

		$this->assertInstanceOf( Purchase_Order_Result::class, $result );
		$this->assertFalse( $result->is_error() );
		$this->assertSame( $invoice, $result->invoice );
		$this->assertSame( 'poModal', $result->modal );
	}

	public function test_handler_custom_template_suffix(): void {
		$invoice = $this->createMock( Invoice::class );
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'get_invoice' )
			->with( 42 )
			->willReturn( $invoice );

		$handler = new Get_Purchase_Order_Handler( $service );
		$query   = new Get_Purchase_Order_Query( id: 42, modal: 'poModal', template: 'table' );

		$result = $handler->handle( $query );

		$this->assertInstanceOf( Purchase_Order_Result::class, $result );
		$this->assertSame( 'table', $result->template );
	}

	public function test_handler_missing_id_returns_error_view_model(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->never() )->method( 'get_invoice' );

		$handler = new Get_Purchase_Order_Handler( $service );
		$query   = new Get_Purchase_Order_Query( id: 0 );

		$result = $handler->handle( $query );

		$this->assertInstanceOf( Purchase_Order_Result::class, $result );
		$this->assertTrue( $result->is_error() );
		$this->assertSame( 'Invoice not found', $result->error_message );
	}

	public function test_handler_invoice_not_found_exception_returns_error_view_model(): void {
		$service = $this->createMock( Finance_Service::class );
		$service->expects( $this->once() )
			->method( 'get_invoice' )
			->with( 999 )
			->willThrowException( new Invoice_Not_Found_Exception( 'Invoice #999 does not exist' ) );

		$handler = new Get_Purchase_Order_Handler( $service );
		$query   = new Get_Purchase_Order_Query( id: 999 );

		$result = $handler->handle( $query );

		$this->assertInstanceOf( Purchase_Order_Result::class, $result );
		$this->assertTrue( $result->is_error() );
		$this->assertSame( 'Invoice #999 does not exist', $result->error_message );
	}
}
