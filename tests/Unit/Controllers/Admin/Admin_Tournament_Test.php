<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\Container\Simple_Container;

require_once __DIR__ . '/../../../wp-stubs.php';

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
        $this->renderer = $this->getMockBuilder( View_Renderer_Interface::class )
            ->onlyMethods( [ 'render' ] )
            ->getMock();
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

    public function test_display_competition_config_page_routes_correctly(): void {
        $controller = $this->getMockBuilder( \stdClass::class )
            ->addMethods( [ 'handle' ] )
            ->getMock();

        $this->container->set( 'tournament_competition_config_admin_controller', $controller );

        $comp_ser = sprintf( 'O:%d:"Racketmanager\Domain\Competition\Competition":0:{}', strlen(  'Racketmanager\Domain\Competition\Competition' ) );
        $vm = unserialize( sprintf( 'O:%d:"Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model":5:{s:11:"competition";%ss:10:"tournament";N;s:13:"rules_options";a:0:{}s:5:"clubs";a:0:{}s:3:"tab";s:7:"general";}', strlen( 'Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model' ), $comp_ser ) );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_competition_config_page();
    }

    public function test_display_event_config_page_routes_correctly(): void {
        $controller = $this->getMockBuilder( \stdClass::class )
            ->addMethods( [ 'handle' ] )
            ->getMock();

        $this->container->set( 'tournament_event_config_admin_controller', $controller );

        $comp_ser = sprintf( 'O:%d:"Racketmanager\Domain\Competition\Competition":0:{}', strlen(  'Racketmanager\Domain\Competition\Competition' ) );
        $vm = unserialize( sprintf( 'O:%d:"Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model":4:{s:11:"competition";%ss:5:"event";O:8:"stdClass":0:{}s:10:"tournament";N;s:9:"new_event";b:0;}', strlen( 'Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model' ), $comp_ser ) );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_event_config_page();
    }

    public function test_display_team_page_routes_correctly(): void {
        $controller = $this->getMockBuilder( \stdClass::class )
            ->addMethods( [ 'handle' ] )
            ->getMock();

        $this->container->set( 'tournament_team_admin_controller', $controller );

        $vm = unserialize( sprintf( 'O:%d:"Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model":9:{s:4:"team";O:8:"stdClass":0:{}s:6:"league";N;s:10:"tournament";N;s:5:"clubs";a:0:{}s:10:"form_title";s:0:""s:11:"form_action";s:0:""s:4:"file";s:8:"team.php"s:6:"season";s:0:""s:10:"match_days";a:0:{}}', strlen( 'Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model' ) ) );

        $controller->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( [ 'view_model' => $vm ] );

        $this->admin_tournament->display_team_page();
    }
}
