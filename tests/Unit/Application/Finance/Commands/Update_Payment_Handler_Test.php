<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Finance\Commands;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Finance\Commands\Update_Payment_Command;
use Racketmanager\Application\Finance\Commands\Update_Payment_Handler;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use RuntimeException;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Update_Payment_Handler_Test extends TestCase {

    public function test_update_payment_command_properties_and_defaults(): void {
        $default_command = new Update_Payment_Command( 'pi_default_123' );
        $this->assertSame( 'pi_default_123', $default_command->reference );
        $this->assertSame( 'paid', $default_command->status );

        $custom_command = new Update_Payment_Command( 'pi_custom_456', 'failed' );
        $this->assertSame( 'pi_custom_456', $custom_command->reference );
        $this->assertSame( 'failed', $custom_command->status );
    }

    public function test_update_payment_handler_delegates_to_finance_service_with_default_status(): void {
        $finance_service = $this->createMock( Finance_Service_Interface::class );
        $finance_service->expects( $this->once() )
            ->method( 'update_payment' )
            ->with( 'pi_test_123', 'paid' );

        $handler = new Update_Payment_Handler( $finance_service );
        $command = new Update_Payment_Command( 'pi_test_123' );

        $handler->handle( $command );
    }

    public function test_update_payment_handler_delegates_to_finance_service_with_custom_status(): void {
        $finance_service = $this->createMock( Finance_Service_Interface::class );
        $finance_service->expects( $this->once() )
            ->method( 'update_payment' )
            ->with( 'pi_test_456', 'pending' );

        $handler = new Update_Payment_Handler( $finance_service );
        $command = new Update_Payment_Command( 'pi_test_456', 'pending' );

        $handler->handle( $command );
    }

    public function test_update_payment_handler_propagates_exceptions(): void {
        $finance_service = $this->createMock( Finance_Service_Interface::class );
        $finance_service->expects( $this->once() )
            ->method( 'update_payment' )
            ->with( 'pi_fail_789', 'failed' )
            ->willThrowException( new RuntimeException( 'Payment update failed' ) );

        $handler = new Update_Payment_Handler( $finance_service );
        $command = new Update_Payment_Command( 'pi_fail_789', 'failed' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Payment update failed' );

        $handler->handle( $command );
    }
}
