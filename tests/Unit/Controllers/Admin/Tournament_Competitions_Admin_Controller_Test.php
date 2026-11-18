<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Competitions_Admin_Controller;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Application\Admin\Ports\Index_Action_Dispatcher_Interface;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Tournament_Competitions_Admin_Controller_Test extends TestCase {

    private Competition_Service $competition_service;
    private Action_Guard_Interface $action_guard;
    private Index_Action_Dispatcher_Interface $index_action_dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->competition_service      = $this->createMock( Competition_Service::class );
        $this->action_guard             = $this->createMock( Action_Guard_Interface::class );
        $this->index_action_dispatcher  = $this->createMock( Index_Action_Dispatcher_Interface::class );
    }

    private function create_controller(): Tournament_Competitions_Admin_Controller {
        return new Tournament_Competitions_Admin_Controller(
            $this->competition_service,
            $this->action_guard,
            $this->index_action_dispatcher
        );
    }

    public function test_capability_failure_stops_before_any_work(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $controller = $this->create_controller();

        $this->expectException( Invalid_Status_Exception::class );
        $controller->competitions_page( array(), array() );
    }

    public function test_get_returns_tournament_competitions_view_model(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->competition_service->expects( self::once() )
            ->method( 'find_competitions_with_summary' )
            ->with( 'open', 'tournament' )
            ->willReturn( array( (object) array( 'id' => 1, 'name' => 'Annual Open' ) ) );

        $controller = $this->create_controller();
        $result     = $controller->competitions_page( array( 'age_group' => 'open' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Competitions_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'Tournament Competitions', $result['view_model']->page_title );
        self::assertSame( 'tournament', $result['view_model']->competition_type );
        self::assertSame( 'open', $result['view_model']->age_group_select );
        self::assertSame( 'racketmanager-tournaments', $result['view_model']->page_name );
        self::assertSame( 'competitions', $result['view_model']->view_name );
        self::assertCount( 1, $result['view_model']->competitions );
        self::assertArrayNotHasKey( 'redirect', $result );
    }

    public function test_post_add_competition_delegates_to_action_dispatcher_and_redirects(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->index_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with(
                array( 'page' => 'racketmanager-tournaments' ),
                self::callback( function ( array $post ) {
                    return isset( $post['addCompetition'] )
                        && 'tournament' === ( $post['type'] ?? '' )
                        && 'Annual Open 2026' === ( $post['competition_name'] ?? '' );
                } )
            )
            ->willReturn(
                new Action_Result_DTO(
                    message: 'Competition added',
                    message_type: Admin_Message_Type::SUCCESS
                )
            );

        $controller = $this->create_controller();
        $result     = $controller->competitions_page(
            array( 'page' => 'racketmanager-tournaments' ),
            array(
                'addCompetition'   => '1',
                'competition_name' => 'Annual Open 2026',
                'age_group'        => 'open',
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=competitions', $result['redirect'] );
    }

    public function test_post_bulk_delete_redirects_back_to_tournament_competitions_screen(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $this->index_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn(
                new Action_Result_DTO(
                    message: 'Deleted',
                    message_type: Admin_Message_Type::SUCCESS
                )
            );

        $controller = $this->create_controller();
        $result     = $controller->competitions_page(
            array( 'page' => 'racketmanager-tournaments' ),
            array( 'doCompDel' => '1' )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=competitions', $result['redirect'] );
    }
}
