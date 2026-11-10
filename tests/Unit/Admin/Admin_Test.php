<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use WP_Error;

require_once __DIR__ . '/../../wp-stubs.php';
require_once __DIR__ . '/../../wp-menu-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_added_actions']       = [];
        $GLOBALS['wp_stubs_added_filters']       = [];
        $GLOBALS['wp_stubs_added_menu_pages']    = [];
        $GLOBALS['wp_stubs_added_submenu_pages'] = [];
        unset( $GLOBALS['wp_stubs_add_submenu_page_returns'] );
        unset( $GLOBALS['wp_stubs_add_submenu_page_return'] );
        unset( $GLOBALS['wp_stubs_add_menu_page_return'] );
        $GLOBALS['wp_stubs_current_user_can']       = true;
        $GLOBALS['wp_stubs_wp_verify_nonce_calls']  = [];
        $GLOBALS['wp_stubs_current_user_can_calls'] = [];
        unset( $GLOBALS['wp_stubs_valid_nonce_actions'] );
        unset( $GLOBALS['wp_stubs_current_user_can_callback'] );
        unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
        Admin::reset_instance();
        Plugin_Bootstrap::reset();
        unset( $GLOBALS['wpdb'] );
        unset( $GLOBALS['racketmanager'] );
    }

    protected function tearDown(): void {
        Admin::reset_instance();
        Plugin_Bootstrap::reset();
        unset( $GLOBALS['wpdb'] );
        unset( $GLOBALS['racketmanager'] );
        unset( $GLOBALS['wp_stubs_added_menu_pages'] );
        unset( $GLOBALS['wp_stubs_added_submenu_pages'] );
        unset( $GLOBALS['wp_stubs_add_submenu_page_returns'] );
        unset( $GLOBALS['wp_stubs_add_submenu_page_return'] );
        unset( $GLOBALS['wp_stubs_add_menu_page_return'] );
        unset( $GLOBALS['wp_stubs_current_user_can'] );
        unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
        unset( $GLOBALS['wp_stubs_author_meta'] );
        unset( $GLOBALS['wp_stubs_user_meta'] );
        unset( $GLOBALS['wp_stubs_update_user_meta'] );
        $this->restore_globals();
        parent::tearDown();
    }

    // =========================================================================
    // Admin::get_instance() tests
    // =========================================================================

    public function test_get_instance_returns_cached_instance_if_set(): void {
        $admin = new Admin( new Simple_Container() );
        self::assertSame( $admin, Admin::get_instance() );
    }

    public function test_get_instance_resolves_from_explicit_container(): void {
        $container = new Simple_Container();
        $admin     = new Admin( $container );
        $container->set( 'admin', fn() => $admin );
        Admin::reset_instance();

        $resolved = Admin::get_instance( $container );
        self::assertSame( $admin, $resolved );
    }

    public function test_get_instance_resolves_from_plugin_bootstrap_container_when_no_argument(): void {
        $container = new Simple_Container();
        $admin     = new Admin( $container );
        $container->set( 'admin', fn() => $admin );
        Plugin_Bootstrap::set_container( $container );
        Admin::reset_instance();

        $resolved = Admin::get_instance();
        self::assertSame( $admin, $resolved );
    }

    public function test_get_instance_resolves_from_racketmanager_argument_container(): void {
        $container = new Simple_Container();
        $admin     = new Admin( $container );
        $container->set( 'admin', fn() => $admin );

        $app            = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $app->container = $container;

        Admin::reset_instance();
        $resolved = Admin::get_instance( null, $app );
        self::assertSame( $admin, $resolved );
    }

    public function test_get_instance_throws_logic_exception_when_uninitialized(): void {
        $this->expectException( LogicException::class );
        $this->expectExceptionMessage( 'Admin instance has not been initialized. Ensure Container_Bootstrap has booted the application container.' );

        Admin::get_instance();
    }

    public function test_get_instance_does_not_access_global_racketmanager_directly(): void {
        $container = new Simple_Container();
        $admin     = new Admin( $container );
        $container->set( 'admin', fn() => $admin );

        $app            = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $app->container = $container;
        $GLOBALS['racketmanager'] = $app;

        Admin::reset_instance();

        $this->expectException( LogicException::class );
        $this->expectExceptionMessage( 'Admin instance has not been initialized. Ensure Container_Bootstrap has booted the application container.' );
        Admin::get_instance();
    }

    // =========================================================================
    // Admin::add_meta_box() tests
    // =========================================================================

    public function test_add_meta_box_with_post_id_zero_renders_leagues_and_skips_fixture_lookup(): void {
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn(
                [
                    (object) [ 'id' => 1, 'name' => 'Premier Division' ],
                    (object) [ 'id' => 2, 'name' => 'Division One' ],
                ]
            );
        $league_repo->expects( self::never() )->method( 'find_by_id' );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'find_by_post' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            $league_repo,
            $fixture_repo
        );

        $post = (object) [ 'ID' => 0 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( "name='curr_match_id' value=\"0\"", $output );
        self::assertStringContainsString( 'Premier Division', $output );
        self::assertStringContainsString( 'Division One', $output );
        self::assertStringNotContainsString( "<select name='season'></select>", $output );
    }

    public function test_add_meta_box_with_saved_post_and_linked_fixture_renders_league_and_dropdowns(): void {
        $league = $this->createStub( League::class );
        $league->id = 10;
        $league->method( 'get_season' )->willReturn( '2026' );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn(
                [
                    (object) [ 'id' => 10, 'name' => 'Men\'s Division 1' ],
                ]
            );
        $league_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );

        $fixture            = new Fixture();
        $fixture->id        = 99;
        $fixture->league_id = 10;
        $fixture->season    = '2026';

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 42 )
            ->willReturn( $fixture );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            $league_repo,
            $fixture_repo
        );

        $post = (object) [ 'ID' => 42 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( "name='curr_match_id' value=\"99\"", $output );
        self::assertStringContainsString( "Men's Division 1", $output );
        self::assertStringContainsString( 'value="10"  selected="selected"', $output );
        self::assertStringContainsString( 'name="racketmanager_match_report_nonce"', $output );
        self::assertStringContainsString( 'data-action="racketmanager_edit_match_report_42"', $output );
        self::assertStringNotContainsString( '_wp_http_referer', $output );
        self::assertStringContainsString( "<select name='season'></select>", $output );
        self::assertStringContainsString( "<select name='match_id'></select>", $output );
    }

    public function test_add_meta_box_with_saved_post_and_no_match_renders_leagues_without_seasons(): void {
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn(
                [
                    (object) [ 'id' => 5, 'name' => 'Ladies Division' ],
                ]
            );
        $league_repo->expects( self::never() )->method( 'find_by_id' );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 100 )
            ->willReturn( null );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            $league_repo,
            $fixture_repo
        );

        $post = (object) [ 'ID' => 100 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( "name='curr_match_id' value=\"0\"", $output );
        self::assertStringContainsString( 'Ladies Division', $output );
        self::assertStringNotContainsString( "<select name='season'></select>", $output );
        self::assertStringNotContainsString( "<select name='match_id'></select>", $output );
    }

    public function test_add_meta_box_with_null_repositories_renders_empty_safely(): void {
        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            null
        );

        $post = (object) [ 'ID' => 12 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertSame( '', $output );
    }

    public function test_add_meta_box_with_empty_leagues_list_renders_empty(): void {
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn( [] );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            $league_repo,
            null
        );

        $post = (object) [ 'ID' => 12 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertSame( '', $output );
    }

    public function test_add_meta_box_selects_matching_league_in_dropdown_and_leaves_others_unselected(): void {
        $league = $this->createStub( League::class );
        $league->id = 10;
        $league->method( 'get_season' )->willReturn( '2026' );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn(
                [
                    (object) [ 'id' => 10, 'name' => 'Premier Division' ],
                    (object) [ 'id' => 20, 'name' => 'Division Two' ],
                ]
            );
        $league_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );

        $fixture            = new Fixture();
        $fixture->id        = 99;
        $fixture->league_id = 10;
        $fixture->season    = '2026';

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 42 )
            ->willReturn( $fixture );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            $league_repo,
            $fixture_repo
        );

        $post = (object) [ 'ID' => 42 ];

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( 'value="10"  selected="selected">Premier Division</option>', $output );
        self::assertStringContainsString( 'value="20" >Division Two</option>', $output );
        self::assertStringNotContainsString( 'value="20"  selected="selected"', $output );
        self::assertStringContainsString( "<select name='season'></select>", $output );
        self::assertStringContainsString( "<select name='match_id'></select>", $output );
    }

    // =========================================================================
    // Admin::edit_match_report() tests
    // =========================================================================

    public function test_edit_match_report_does_nothing_if_post_id_is_missing(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [];
        $admin->edit_match_report();
    }

    public function test_edit_match_report_links_new_match_when_no_previous_match(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'update_post_id' )
            ->with( 101, 55 )
            ->willReturn( true );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'curr_match_id'                    => '0',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_detaches_previous_match_when_switching_matches(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $calls        = [];

        $existing_match          = new Fixture();
        $existing_match->id      = 101;
        $existing_match->post_id = 77;

        $target_match          = new Fixture();
        $target_match->id      = 202;
        $target_match->post_id = 0;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 77 )
            ->willReturn( $existing_match );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 202 )
            ->willReturn( $target_match );

        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'update_post_id' )
            ->willReturnCallback(
                function ( int $match_id, int $post_id ) use ( &$calls ): bool {
                    $calls[] = [
                        'id'      => $match_id,
                        'post_id' => $post_id,
                    ];
                    return true;
                }
            );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();

        self::assertSame(
            [
                [
                    'id'      => 202,
                    'post_id' => 77,
                ],
                [
                    'id'      => 101,
                    'post_id' => 0,
                ],
            ],
            $calls
        );
    }

    public function test_edit_match_report_prevents_detaching_when_post_has_no_linked_fixture_and_curr_match_id_is_provided(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 77 )
            ->willReturn( null );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_prevents_theft_when_target_fixture_is_already_linked_to_another_post(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $existing_match          = new Fixture();
        $existing_match->id      = 101;
        $existing_match->post_id = 77;

        $target_match          = new Fixture();
        $target_match->id      = 202;
        $target_match->post_id = 99;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 77 )
            ->willReturn( $existing_match );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 202 )
            ->willReturn( $target_match );

        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_rejects_negative_post_id(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin( null, null, $this->createStub( Admin_Display::class ), new Asset_Manager(), null, null, $fixture_repo );

        $_POST = [
            'post_ID'                          => '-77',
            'match_id'                         => '101',
            'curr_match_id'                    => '0',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_rejects_exponent_match_id(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin( null, null, $this->createStub( Admin_Display::class ), new Asset_Manager(), null, null, $fixture_repo );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '1e2',
            'curr_match_id'                    => '0',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_verifies_nonce_action_tied_to_post_id(): void {
        $admin = new Admin( new Simple_Container() );

        $GLOBALS['wp_stubs_valid_nonce_actions'] = [ 'racketmanager_edit_match_report_999' ];

        $_POST = [
            'post_ID'                          => '55',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();

        self::assertNotEmpty( $GLOBALS['wp_stubs_wp_verify_nonce_calls'] );
        self::assertSame( 'racketmanager_edit_match_report_55', $GLOBALS['wp_stubs_wp_verify_nonce_calls'][0]['action'] );
    }

    public function test_edit_match_report_does_nothing_if_match_id_unchanged(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $fixture = new Fixture();
        $fixture->id = 202;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 77 )
            ->willReturn( $fixture );

        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '202',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_rejects_malformed_current_match_id(): void {
        $existing_fixture = new Fixture();
        $existing_fixture->id = 101;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_post' )->willReturn( $existing_fixture );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '-101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_not_throw_if_fixture_repository_is_null(): void {
        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            null
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
        self::assertTrue( true );
    }

    public function test_edit_match_report_links_match_without_error_when_curr_match_id_is_missing(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'update_post_id' )
            ->with( 101, 55 )
            ->willReturn( true );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_when_match_id_and_curr_match_id_are_missing(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_if_nonce_missing(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'  => '55',
            'match_id' => '101',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_if_nonce_invalid(): void {
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'invalid_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_if_user_cannot_edit_post(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $GLOBALS['wp_stubs_current_user_can_calls'] = [];

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();

        self::assertCount( 1, $GLOBALS['wp_stubs_current_user_can_calls'] );
        self::assertSame( 'edit_post', $GLOBALS['wp_stubs_current_user_can_calls'][0]['capability'] );
        self::assertSame( [ 55 ], $GLOBALS['wp_stubs_current_user_can_calls'][0]['args'] );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState( false )]
    public function test_edit_match_report_does_nothing_during_autosave(): void {
        if ( ! defined( 'DOING_AUTOSAVE' ) ) {
            define( 'DOING_AUTOSAVE', true );
        }

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_if_hook_and_post_id_mismatch(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report( 55 );
    }

    public function test_edit_match_report_does_nothing_if_post_id_or_match_id_is_array(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => [ '55' ],
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => [ '101' ],
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_prevents_malicious_cross_post_detachment_when_existing_match_mismatches(): void {
        $existing_match     = new Fixture();
        $existing_match->id = 101;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )->method( 'find_by_post' )->with( 77 )->willReturn( $existing_match );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '999',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_accepts_hook_post_id_when_matching(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'update_post_id' )
            ->with( 101, 55 )
            ->willReturn( true );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report( 55 );
    }

    public function test_edit_match_report_prevents_theft_when_no_prior_fixture_but_target_linked_to_another_post(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $target_match          = new Fixture();
        $target_match->id      = 202;
        $target_match->post_id = 99;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 55 )
            ->willReturn( null );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 202 )
            ->willReturn( $target_match );

        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '202',
            'curr_match_id'                    => '0',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_allows_reassigning_when_target_fixture_already_belongs_to_current_post(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $calls        = [];

        $existing_match          = new Fixture();
        $existing_match->id      = 101;
        $existing_match->post_id = 77;

        $target_match          = new Fixture();
        $target_match->id      = 202;
        $target_match->post_id = 77;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 77 )
            ->willReturn( $existing_match );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 202 )
            ->willReturn( $target_match );

        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'update_post_id' )
            ->willReturnCallback(
                function ( int $match_id, int $post_id ) use ( &$calls ): bool {
                    $calls[] = [
                        'id'      => $match_id,
                        'post_id' => $post_id,
                    ];
                    return true;
                }
            );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '77',
            'match_id'                         => '202',
            'curr_match_id'                    => '101',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();

        self::assertSame(
            [
                [
                    'id'      => 202,
                    'post_id' => 77,
                ],
                [
                    'id'      => 101,
                    'post_id' => 0,
                ],
            ],
            $calls
        );
    }

    public function test_edit_match_report_does_nothing_if_curr_match_id_is_array(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'curr_match_id'                    => [ '0' ],
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_does_nothing_if_nonce_is_array(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '55',
            'match_id'                         => '101',
            'racketmanager_match_report_nonce' => [ 'test_nonce' ],
        ];

        $admin->edit_match_report();
    }

    public function test_edit_match_report_rejects_zero_post_id(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $admin = new Admin(
            null,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            null,
            null,
            $fixture_repo
        );

        $_POST = [
            'post_ID'                          => '0',
            'match_id'                         => '101',
            'curr_match_id'                    => '0',
            'racketmanager_match_report_nonce' => 'test_nonce',
        ];

        $admin->edit_match_report();
    }

    // =========================================================================
    // Admin::custom_user_profile_fields() tests
    // =========================================================================

    public function test_custom_user_profile_fields_renders_dedicated_nonce_and_fields(): void {
        $admin = new Admin( new Simple_Container() );

        $GLOBALS['wp_stubs_user_meta'][12] = [
            'gender'        => 'M',
            'contactno'     => '0123456789',
            'btm'           => '123456',
            'year_of_birth' => '1990',
            'remove_date'   => '2026-01-01',
            'locked_date'   => '2026-02-01',
        ];

        ob_start();
        $admin->custom_user_profile_fields( (object) [ 'ID' => 12 ] );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'name="racketmanager_profile_nonce"', $output );
        self::assertStringContainsString( 'data-action="racketmanager_update_extra_profile_fields_12"', $output );
        self::assertStringNotContainsString( '_wp_http_referer', $output );
        self::assertStringContainsString( 'id="genderM" value="M"  checked="checked"', $output );
        self::assertStringContainsString( 'name="contactno" id="contactno" value="0123456789"', $output );
        self::assertStringContainsString( 'id="btm" name="btm" value="123456"', $output );
        self::assertStringContainsString( 'name="year_of_birth" id="year_of_birth" value="1990"', $output );
        self::assertStringContainsString( 'name="remove_date" id="remove_date" value="2026-01-01"', $output );
        self::assertStringContainsString( 'name="locked_date" id="locked_date" value="2026-02-01"', $output );
    }

    public function test_custom_user_profile_fields_renders_female_gender_checked(): void {
        $admin = new Admin( new Simple_Container() );

        $GLOBALS['wp_stubs_user_meta'][13] = [
            'gender' => 'F',
        ];

        ob_start();
        $admin->custom_user_profile_fields( (object) [ 'ID' => 13 ] );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'id="genderF" value="F"  checked="checked"', $output );
    }

    // =========================================================================
    // Admin::update_extra_profile_fields() tests
    // =========================================================================

    public function test_update_extra_profile_fields_updates_all_meta_with_dedicated_nonce(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'gender'                      => 'F',
            'contactno'                   => '07700900000',
            'btm'                         => '987654',
            'year_of_birth'               => '1995',
            'remove_date'                 => '2026-03-01',
            'locked_date'                 => '2026-04-01',
        ];

        $admin->update_extra_profile_fields( 15 );

        self::assertSame( [ 'F' ], $GLOBALS['wp_stubs_user_meta'][15]['gender'] );
        self::assertSame( [ '07700900000' ], $GLOBALS['wp_stubs_user_meta'][15]['contactno'] );
        self::assertSame( [ 987654 ], $GLOBALS['wp_stubs_user_meta'][15]['btm'] );
        self::assertSame( [ 1995 ], $GLOBALS['wp_stubs_user_meta'][15]['year_of_birth'] );
        self::assertSame( [ '2026-03-01' ], $GLOBALS['wp_stubs_user_meta'][15]['remove_date'] );
        self::assertSame( [ '2026-04-01' ], $GLOBALS['wp_stubs_user_meta'][15]['locked_date'] );
    }

    public function test_update_extra_profile_fields_updates_meta_with_core_wpnonce(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            '_wpnonce' => 'test_nonce',
            'gender'   => 'M',
        ];

        $admin->update_extra_profile_fields( 16 );

        self::assertSame( [ 'M' ], $GLOBALS['wp_stubs_user_meta'][16]['gender'] );
    }

    public function test_update_extra_profile_fields_does_nothing_if_user_cannot_edit(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $admin                                = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'gender'                      => 'F',
        ];

        $admin->update_extra_profile_fields( 17 );

        self::assertArrayNotHasKey( 17, $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_does_nothing_if_user_id_invalid(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'gender'                      => 'F',
        ];

        $admin->update_extra_profile_fields( 0 );
        $admin->update_extra_profile_fields( -1 );

        self::assertEmpty( $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_does_nothing_if_nonce_missing(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'gender' => 'F',
        ];

        $admin->update_extra_profile_fields( 18 );

        self::assertArrayNotHasKey( 18, $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_does_nothing_if_nonce_invalid(): void {
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
        $admin                                      = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'invalid_nonce',
            'gender'                      => 'F',
        ];

        $admin->update_extra_profile_fields( 19 );

        self::assertArrayNotHasKey( 19, $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_ignores_malformed_array_inputs(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'gender'                      => [ 'F' ],
            'btm'                         => [ '123' ],
            'contactno'                   => '0123456789',
        ];

        $admin->update_extra_profile_fields( 20 );

        self::assertSame( [ '0123456789' ], $GLOBALS['wp_stubs_user_meta'][20]['contactno'] );
        self::assertArrayNotHasKey( 'gender', $GLOBALS['wp_stubs_user_meta'][20] );
        self::assertArrayNotHasKey( 'btm', $GLOBALS['wp_stubs_user_meta'][20] );
    }

    public function test_update_extra_profile_fields_verifies_nonce_actions_and_capability_with_user_id(): void {
        $admin = new Admin( new Simple_Container() );

        $GLOBALS['wp_stubs_valid_nonce_actions'] = [ 'update-user_25' ];

        $_POST = [
            '_wpnonce' => 'test_nonce',
            'gender'   => 'F',
        ];

        $admin->update_extra_profile_fields( 25 );

        self::assertSame( [ 'F' ], $GLOBALS['wp_stubs_user_meta'][25]['gender'] );
        self::assertSame( 'edit_user', $GLOBALS['wp_stubs_current_user_can_calls'][0]['capability'] );
        self::assertSame( [ 25 ], $GLOBALS['wp_stubs_current_user_can_calls'][0]['args'] );

        $_POST = [
            '_wpnonce' => 'test_nonce',
            'gender'   => 'M',
        ];

        $admin->update_extra_profile_fields( 26 );
        self::assertArrayNotHasKey( 26, $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_ignores_invalid_gender(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'gender'                      => 'X',
        ];

        $admin->update_extra_profile_fields( 30 );

        self::assertArrayNotHasKey( 30, $GLOBALS['wp_stubs_user_meta'] ?? [] );
    }

    public function test_update_extra_profile_fields_falls_back_to_core_nonce_when_plugin_nonce_invalid_or_malformed(): void {
        $admin = new Admin( new Simple_Container() );

        $GLOBALS['wp_stubs_valid_nonce_actions'] = [ 'update-user_40' ];

        $_POST = [
            'racketmanager_profile_nonce' => [ 'malformed_array' ],
            '_wpnonce'                    => 'valid_core_nonce',
            'contactno'                   => '0123456789',
        ];

        $admin->update_extra_profile_fields( 40 );

        self::assertSame( [ '0123456789' ], $GLOBALS['wp_stubs_user_meta'][40]['contactno'] );
    }

    public function test_update_extra_profile_fields_ignores_array_inputs_and_updates_scalar_date_and_int_fields(): void {
        $admin = new Admin( new Simple_Container() );

        $_POST = [
            'racketmanager_profile_nonce' => 'test_nonce',
            'remove_date'                 => [ '2026-03-01' ],
            'locked_date'                 => '2026-04-01',
            'year_of_birth'               => [ '1995' ],
            'btm'                         => '123456',
        ];

        $admin->update_extra_profile_fields( 41 );

        self::assertArrayNotHasKey( 'remove_date', $GLOBALS['wp_stubs_user_meta'][41] ?? [] );
        self::assertArrayNotHasKey( 'year_of_birth', $GLOBALS['wp_stubs_user_meta'][41] ?? [] );
        self::assertSame( [ '2026-04-01' ], $GLOBALS['wp_stubs_user_meta'][41]['locked_date'] );
        self::assertSame( [ 123456 ], $GLOBALS['wp_stubs_user_meta'][41]['btm'] );
    }

    // =========================================================================
    // Admin::schedule_player_ratings() tests
    // =========================================================================

    public function test_schedule_player_ratings_returns_error_when_container_is_null(): void {
        $admin  = new Admin( null );
        $result = $admin->schedule_player_ratings();

        self::assertInstanceOf( WP_Error::class, $result );
        self::assertSame( 'service_unavailable', $result->get_error_code() );
    }

    public function test_schedule_player_ratings_returns_error_when_player_service_is_missing(): void {
        $container = new Simple_Container();

        $admin  = new Admin( $container );
        $result = $admin->schedule_player_ratings();

        self::assertInstanceOf( WP_Error::class, $result );
        self::assertSame( 'service_unavailable', $result->get_error_code() );
    }

    public function test_schedule_player_ratings_resolves_registered_player_service_alias(): void {
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( true );

        $container = new Simple_Container();
        $container->set( 'player_service', $player_service );
        $container->alias( Player_Service::class, 'player_service' );

        $admin = new Admin( $container );

        self::assertTrue( $admin->schedule_player_ratings() );
    }

    // =========================================================================
    // Admin::menu() tests
    // =========================================================================

    public function test_menu_registers_top_level_page_with_expected_arguments_and_asset_hooks(): void {
        $admin_display = $this->createStub( Admin_Display::class );
        $admin         = new Admin(
            new Simple_Container(),
            null,
            $admin_display,
            new Asset_Manager()
        );

        $admin->menu();

        $menu_pages = $GLOBALS['wp_stubs_added_menu_pages'] ?? [];
        self::assertCount( 1, $menu_pages );

        $top_page = $menu_pages[0];
        self::assertSame( 'RacketManager', $top_page['page_title'] );
        self::assertSame( 'RacketManager', $top_page['menu_title'] );
        self::assertSame( 'racket_manager', $top_page['capability'] );
        self::assertSame( 'racketmanager', $top_page['menu_slug'] );
        self::assertSame( [ $admin_display, 'display' ], $top_page['callback'] );
        self::assertStringStartsWith( 'data:image/svg+xml;base64,', $top_page['icon_url'] );
        self::assertSame( 2, $top_page['position'] );

        $actions = $GLOBALS['wp_stubs_added_actions'] ?? [];
        self::assertArrayHasKey( 'admin_print_scripts-toplevel_page_racketmanager', $actions );
        $top_actions = $actions['admin_print_scripts-toplevel_page_racketmanager'];
        self::assertCount( 2, $top_actions );
        self::assertSame( [ $admin, 'load_scripts' ], $top_actions[0]['callback'] );
        self::assertSame( [ $admin, 'load_styles' ], $top_actions[1]['callback'] );
    }

    public function test_menu_registers_all_twelve_submenus_in_exact_order_with_asset_hooks(): void {
        $admin_display = $this->createStub( Admin_Display::class );
        $admin         = new Admin(
            new Simple_Container(),
            null,
            $admin_display,
            new Asset_Manager()
        );

        $admin->menu();

        $submenu_pages = $GLOBALS['wp_stubs_added_submenu_pages'] ?? [];
        self::assertCount( 12, $submenu_pages );

        $expected_submenus = [
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'RacketManager',
                'menu_title'  => 'Competitions',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Leagues',
                'menu_title'  => 'Leagues',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-leagues',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Cups',
                'menu_title'  => 'Cups',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-cups',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Tournaments',
                'menu_title'  => 'Tournaments',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-tournaments',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Clubs',
                'menu_title'  => 'Clubs',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-clubs',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Results',
                'menu_title'  => 'Results',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-results',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Players',
                'menu_title'  => 'Players',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-players',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Seasons',
                'menu_title'  => 'Seasons',
                'capability'  => 'racketmanager_settings',
                'menu_slug'   => 'racketmanager-seasons',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Finances',
                'menu_title'  => 'Finances',
                'capability'  => 'racket_manager',
                'menu_slug'   => 'racketmanager-finances',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Settings',
                'menu_title'  => 'Settings',
                'capability'  => 'racketmanager_settings',
                'menu_slug'   => 'racketmanager-settings',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Import',
                'menu_title'  => 'Import',
                'capability'  => 'import_leagues',
                'menu_slug'   => 'racketmanager-import',
            ],
            [
                'parent_slug' => 'racketmanager',
                'page_title'  => 'Documentation',
                'menu_title'  => 'Documentation',
                'capability'  => 'view_leagues',
                'menu_slug'   => 'racketmanager-doc',
            ],
        ];

        $actions = $GLOBALS['wp_stubs_added_actions'] ?? [];

        foreach ( $expected_submenus as $index => $expected ) {
            $actual = $submenu_pages[ $index ];
            self::assertSame( $expected['parent_slug'], $actual['parent_slug'], "Mismatch in parent_slug at index $index" );
            self::assertSame( $expected['page_title'], $actual['page_title'], "Mismatch in page_title at index $index" );
            self::assertSame( $expected['menu_title'], $actual['menu_title'], "Mismatch in menu_title at index $index" );
            self::assertSame( $expected['capability'], $actual['capability'], "Mismatch in capability at index $index" );
            self::assertSame( $expected['menu_slug'], $actual['menu_slug'], "Mismatch in menu_slug at index $index" );
            self::assertSame( [ $admin_display, 'display' ], $actual['callback'], "Mismatch in callback at index $index" );

            $hook = 'admin_print_scripts-racketmanager_page_' . $expected['menu_slug'];
            self::assertArrayHasKey( $hook, $actions, "Missing script/style hook $hook" );
            self::assertCount( 2, $actions[ $hook ], "Expected 2 actions for hook $hook" );
            self::assertSame( [ $admin, 'load_scripts' ], $actions[ $hook ][0]['callback'] );
            self::assertSame( [ $admin, 'load_styles' ], $actions[ $hook ][1]['callback'] );
        }
    }

    public function test_menu_registers_plugin_action_links_filter(): void {
        $admin = new Admin(
            new Simple_Container(),
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        $admin->menu();

        $filters    = $GLOBALS['wp_stubs_added_filters'] ?? [];
        $filter_tag = 'plugin_action_links_' . RACKETMANAGER_PLUGIN_BASENAME;
        self::assertArrayHasKey( $filter_tag, $filters );
        self::assertSame( [ $admin, 'plugin_actions' ], $filters[ $filter_tag ][0]['callback'] );
    }

    public function test_menu_with_null_admin_display_registers_without_throwing(): void {
        $admin = new Admin(
            new Simple_Container(),
            null,
            null,
            new Asset_Manager()
        );

        $admin->menu();

        $menu_pages    = $GLOBALS['wp_stubs_added_menu_pages'] ?? [];
        $submenu_pages = $GLOBALS['wp_stubs_added_submenu_pages'] ?? [];

        self::assertCount( 1, $menu_pages );
        self::assertSame( [ null, 'display' ], $menu_pages[0]['callback'] );
        self::assertCount( 12, $submenu_pages );
        self::assertSame( [ null, 'display' ], $submenu_pages[0]['callback'] );
    }

    public function test_menu_handles_false_page_return_from_add_submenu_page(): void {
        $GLOBALS['wp_stubs_add_submenu_page_returns'] = [
            'racketmanager-import' => false,
        ];

        $admin = new Admin(
            new Simple_Container(),
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        $admin->menu();

        $actions = $GLOBALS['wp_stubs_added_actions'] ?? [];
        self::assertArrayHasKey( 'admin_print_scripts-', $actions );
        self::assertSame( [ $admin, 'load_scripts' ], $actions['admin_print_scripts-'][0]['callback'] );
        self::assertSame( [ $admin, 'load_styles' ], $actions['admin_print_scripts-'][1]['callback'] );
    }

    // =========================================================================
    // Admin::scroll_top() tests
    // =========================================================================

    public function test_scroll_top_renders_top_anchor_with_translated_text(): void {
        $admin = new Admin( new Simple_Container() );

        ob_start();
        $admin->scroll_top();
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'Top', $output );
        self::assertStringContainsString( '<a class="go-top dashicons dashicons-arrow-up-alt2">Top</a>', $output );
    }
}
