<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Seasons_Admin_Controller;
use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Seasons_Admin_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Season_Service $season_service;
    private Action_Guard_Interface $guard;
    private Season_Action_Dispatcher $dispatcher;
    private Seasons_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->season_service = $this->createMock( Season_Service::class );
        $this->guard          = $this->createMock( Action_Guard_Interface::class );
        $this->dispatcher     = new Season_Action_Dispatcher(
            $this->season_service,
            $this->guard
        );

        $this->controller = new Seasons_Admin_Controller(
            $this->season_service,
            $this->dispatcher,
            $this->guard
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_capability_check_fails_on_get(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_seasons' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->seasons_page( array(), array() );
    }

    public function test_get_seasons_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_seasons' );

        $season = new Season();
        $season->set_name( '2025' );
        $this->season_service->method( 'get_all_seasons' )
            ->willReturn( array( $season ) );

        $result = $this->controller->seasons_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Seasons_Page_View_Model::class, $result['view_model'] );
        self::assertCount( 1, $result['view_model']->seasons );
        self::assertSame( $season, $result['view_model']->seasons[0] );
        self::assertNull( $result['view_model']->season_name );
        self::assertSame( array(), $result['view_model']->errors->all() );
    }

    public function test_get_seasons_preserves_flash_data(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_seasons' );

        $this->season_service->method( 'get_all_seasons' )
            ->willReturn( array() );

        $flash_data = array(
            'errors'      => array( 'seasonName' => 'Season already exists' ),
            'season_name' => '2025',
        );

        $result = $this->controller->seasons_page( array(), array(), $flash_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( '2025', $result['view_model']->season_name );
        self::assertTrue( $result['view_model']->errors->has( 'seasonName' ) );
        self::assertSame( 'Season already exists', $result['view_model']->errors->message( 'seasonName' ) );
    }

    public function test_post_add_season_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-season', 'edit_seasons' );

        $this->season_service->expects( self::once() )
            ->method( 'create_season' )
            ->with( '2025' )
            ->willReturn( true );

        $result = $this->controller->seasons_page(
            array( 'page' => 'racketmanager-seasons' ),
            array(
                'addSeason'  => '1',
                'seasonName' => '2025',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-seasons', $result['redirect'] );
        self::assertSame( 'Season added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_delete_season_dispatches_and_redirects(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_seasons-bulk', 'edit_seasons' );

        $this->season_service->expects( self::once() )
            ->method( 'delete_season' )
            ->with( 2024 )
            ->willReturn( true );

        $result = $this->controller->seasons_page(
            array( 'page' => 'racketmanager-seasons' ),
            array(
                'doSeasonDel' => '1',
                'action'      => 'delete',
                'season'      => array( '2024' ),
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-seasons', $result['redirect'] );
        self::assertSame( 'Season(s) deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
