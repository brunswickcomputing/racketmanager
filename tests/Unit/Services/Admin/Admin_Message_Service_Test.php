<?php
declare(strict_types=1);

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\show_alert' ) ) {
        function show_alert( string $message, string $type, ?string $template = null ): string {
            return "<div class='alert alert-{$type}'>{$message}</div>";
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Admin {

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Services\Admin\Admin_Message_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Admin_Message_Service_Test extends TestCase {

    public function test_set_message_stores_correct_type(): void {
        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $service = new Admin_Message_Service( $flash_store );

        $service->set_message( 'Error message', true );
        
        ob_start();
        try {
            $service->show_message();
            $output = ob_get_clean();
        } catch ( \Throwable $e ) {
            ob_end_clean();
            throw $e;
        }

        $this->assertStringContainsString( 'Error message', $output );
        $this->assertStringContainsString( "class='alert alert-danger'", $output );
    }

    public function test_set_message_stores_success_type_by_default(): void {
        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $service = new Admin_Message_Service( $flash_store );

        $service->set_message( 'Success message' );
        
        ob_start();
        try {
            $service->show_message();
            $output = ob_get_clean();
        } catch ( \Throwable $e ) {
            ob_end_clean();
            throw $e;
        }

        $this->assertStringContainsString( 'Success message', $output );
        $this->assertStringContainsString( "class='alert alert-success'", $output );
    }

    public function test_set_flash_message_delegates_to_store(): void {
        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $service = new Admin_Message_Service( $flash_store );

        $flash_store->expects( $this->once() )
            ->method( 'set' )
            ->with( 'Flash message', 'warning' );

        $service->set_flash_message( 'Flash message', 'warning' );
    }

    public function test_pop_flash_message_delegates_to_store(): void {
        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $service = new Admin_Message_Service( $flash_store );

        $flash_store->expects( $this->once() )
            ->method( 'pop' )
            ->willReturn( [ 'message' => 'Popped message' ] );

        $result = $service->pop_flash_message();
        $this->assertEquals( 'Popped message', $result['message'] );
    }
}
}
