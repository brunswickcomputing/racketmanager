<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\Container\Simple_Container;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Throwable;

interface Admin_Controller_Mock_Interface {
    public function teams_page();
    public function handle();
}

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations] final
class Admin_Tournament_Test extends TestCase {

    private Admin_Tournament $admin_tournament;
    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $racketmanager     = $this->createStub( RacketManager::class );
        $renderer          = $this->createMock( View_Renderer_Interface::class );
        $flash_store = $this->createStub( Admin_Flash_Message_Store::class );
        $message_service = new Admin_Message_Service( $flash_store );
        $this->container          = new Simple_Container();
        $racketmanager->container = $this->container;

        $this->admin_tournament = new Admin_Tournament(
            $racketmanager,
            $renderer,
            $message_service
        );
    }

    public function test_constructor_initializes_properties(): void {
        self::assertInstanceOf( Admin_Tournament::class, $this->admin_tournament );
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function test_display_teams_list_calls_apply_result_message(): void {
        $controller = $this->createStub( Admin_Controller_Mock_Interface::class );

        $this->container->set( 'tournament_teams_admin_controller', $controller );

        $vm = ( new ReflectionClass( Tournament_Teams_List_Page_View_Model::class ) )
            ->newInstanceWithoutConstructor();

        $result = [
            'view_model' => $vm,
            'message' => 'Test message',
            'message_type' => 'success'
        ];

        $controller->method( 'teams_page' )->willReturn( $result );

        ob_start();
        try {
            $this->admin_tournament->display_teams_list();
            $output = ob_get_clean();
        } catch ( Throwable $e ) {
            ob_end_clean();
            throw $e;
        }

        $this->assertStringContainsString( 'Test message', $output );
    }

    public function test_display_competition_config_page_routes_correctly(): void {
        $controller = $this->createMock( Admin_Controller_Mock_Interface::class );

        $this->container->set( 'tournament_competition_config_admin_controller', $controller );

        $competition = $this->createStub( Competition::class );
        $vm = new Tournament_Competition_Config_Page_View_Model(
            $competition,
            null,
            [],
            [],
            [],
            'general'
        );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_competition_config_page();
    }

    public function test_display_event_config_page_routes_correctly(): void {
        $controller = $this->createMock( Admin_Controller_Mock_Interface::class );

        $this->container->set( 'tournament_event_config_admin_controller', $controller );

        $competition = $this->createStub( Competition::class );
        $vm = new Tournament_Event_Config_Page_View_Model(
            $competition,
            new stdClass(),
            null,
            false
        );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_event_config_page();
    }

    public function test_display_team_page_routes_correctly(): void {
        $controller = $this->createMock( Admin_Controller_Mock_Interface::class );

        $this->container->set( 'tournament_team_admin_controller', $controller );

        $vm = new Tournament_Team_Page_View_Model(
            new stdClass(),
            null,
            null,
            [],
            '',
            '',
            'team.php',
            '',
            []
        );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_team_page();
    }
}
