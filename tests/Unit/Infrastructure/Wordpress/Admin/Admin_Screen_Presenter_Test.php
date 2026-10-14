<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Screen_Presenter_Test extends TestCase {

    public function test_renders_view_model_and_shows_result_message(): void {
        $vm       = new stdClass();
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::once() )->method( 'render' )->with( 'admin/page', $vm );

        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );

        ob_start();
        $presenter->present( array( 'view_model' => $vm, 'message' => 'Done', 'message_type' => false ), stdClass::class, 'admin/page' );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'Done', $output );
    }

    public function test_template_can_be_resolved_from_view_model(): void {
        $vm       = (object) array( 'file' => 'player-team' );
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::once() )->method( 'render' )->with( 'admin/includes/teams/player-team', $vm );

        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );

        $presenter->present(
            array( 'view_model' => $vm ),
            stdClass::class,
            static fn( object $view_model ): string => 'admin/includes/teams/' . $view_model->file
        );
    }

    public function test_strict_mode_throws_for_missing_view_model_without_rendering(): void {
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );

        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );

        $this->expectException( Invalid_Status_Exception::class );
        $presenter->present( array(), stdClass::class, 'admin/page' );
    }

    public function test_lenient_mode_shows_message_and_skips_rendering(): void {
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );

        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );

        ob_start();
        $presenter->present( array( 'message' => 'Problem', 'message_type' => true ), stdClass::class, 'admin/page', false );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'Problem', $output );
    }

    public function test_apply_flash_moves_pending_flash_into_current_request(): void {
        $store = $this->createMock( Admin_Flash_Message_Store::class );
        $store->expects( self::once() )->method( 'pop' )->willReturn( array( 'message' => 'From redirect', 'message_type' => false ) );
        $vm       = new stdClass();
        $renderer = $this->createStub( View_Renderer_Interface::class );

        $presenter = new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $store ) );
        $presenter->apply_flash();

        ob_start();
        $presenter->present( array( 'view_model' => $vm ), stdClass::class, 'admin/page' );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'From redirect', $output );
    }

    public function test_result_message_overrides_flash_message(): void {
        $store = $this->createStub( Admin_Flash_Message_Store::class );
        $store->method( 'pop' )->willReturn( array( 'message' => 'Old flash' ) );
        $presenter = new Admin_Screen_Presenter( $this->createStub( View_Renderer_Interface::class ), new Admin_Message_Service( $store ) );
        $presenter->apply_flash();

        ob_start();
        $presenter->present( array( 'view_model' => new stdClass(), 'message' => 'Fresh' ), stdClass::class, 'admin/page' );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'Fresh', $output );
        self::assertStringNotContainsString( 'Old flash', $output );
    }
}
