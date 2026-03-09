<?php
declare(strict_types=1);

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\show_alert' ) ) {
        function show_alert( string $message, string $type, ?string $template = null ): string {
            return "<div class='alert alert-{$type}'>{$message}</div>";
        }
    }
}

namespace Racketmanager\Tests\Unit {

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\Container\Simple_Container;

require_once __DIR__ . '/../wp-stubs.php';

final class Admin_Tournament_Test extends TestCase {

    private $racketmanager;
    private $renderer;
    private $message_service;
    private $admin_tournament;
    private $container;
    private $flash_store;

    protected function setUp(): void {
        parent::setUp();
        $this->racketmanager = $this->createMock( RacketManager::class );
        $this->renderer = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $this->message_service = new Admin_Message_Service( $this->flash_store );
        $this->container = new Simple_Container();
        $this->racketmanager->container = $this->container;

        $this->admin_tournament = new Admin_Tournament(
            $this->racketmanager,
            $this->renderer,
            $this->message_service
        );
    }

    public function test_constructor_initializes_properties(): void {
        self::assertInstanceOf( Admin_Tournament::class, $this->admin_tournament );
    }

    public function test_display_teams_list_calls_apply_result_message(): void {
        $controller = $this->getMockBuilder( \stdClass::class )
            ->addMethods( [ 'teams_page' ] )
            ->getMock();

        $this->container->set( 'tournament_teams_admin_controller', $controller );

        $vm = ( new \ReflectionClass( \Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model::class ) )
            ->newInstanceWithoutConstructor();

        $result = [
            'view_model' => $vm,
            'message' => 'Test message',
            'message_type' => 'success'
        ];

        $controller->method( 'teams_page' )->willReturn( $result );

        ob_start();
        $this->admin_tournament->display_teams_list();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Test message', $output );
    }
}
}
