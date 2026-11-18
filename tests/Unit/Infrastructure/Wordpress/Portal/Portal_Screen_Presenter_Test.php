<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Admin\Ports\Flash_Message_Store_Interface;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Auth_Guard;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Layout_View_Model;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_View_Model;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Registry;
use Racketmanager\Services\Admin\Admin_Message_Service;

final class Portal_Screen_Presenter_Test extends TestCase {

    private Admin_Message_Service $message_service;
    private Portal_Section_Registry $registry;
    private Manage_Url_Resolver_Interface $url_resolver;
    private Portal_Auth_Guard $auth_guard;

    protected function setUp(): void {
        parent::setUp();
        $flash_store           = $this->createStub( Flash_Message_Store_Interface::class );
        $flash_store->method( 'pop' )->willReturn( [] );
        $this->message_service = new Admin_Message_Service( $flash_store );
        $this->registry        = new Portal_Section_Registry();
        $this->url_resolver    = $this->createStub( Manage_Url_Resolver_Interface::class );
        $this->url_resolver->method( 'get_url' )->willReturnCallback(
            fn( string $sec ) => 'https://example.test/manage/' . $sec
        );
        $this->auth_guard       = $this->createStub( Portal_Auth_Guard::class );
        $this->auth_guard->method( 'user_can' )->willReturn( true );
    }

    private function create_presenter( ?View_Renderer_Interface $renderer = null, ?Portal_Auth_Guard $auth_guard = null ): Portal_Screen_Presenter {
        return new Portal_Screen_Presenter(
            $renderer ?? $this->createStub( View_Renderer_Interface::class ),
            $this->message_service,
            $this->registry,
            $this->url_resolver,
            $auth_guard ?? $this->auth_guard
        );
    }

    public function test_builds_navigation_for_authorized_sections(): void {
        $presenter = $this->create_presenter();
        $nav_vm    = $presenter->build_navigation( 'clubs' );

        $this->assertNotEmpty( $nav_vm->items );
        $found_clubs = false;
        foreach ( $nav_vm->items as $item ) {
            if ( 'clubs' === $item->slug ) {
                $found_clubs = true;
                $this->assertTrue( $item->is_active );
                $this->assertSame( 'https://example.test/manage/clubs', $item->url );
            } else {
                $this->assertFalse( $item->is_active );
            }
        }
        $this->assertTrue( $found_clubs );
    }

    public function test_builds_navigation_for_unported_sections(): void {
        $this->registry->set_ported( 'clubs', false );
        $presenter = $this->create_presenter();
        $nav_vm    = $presenter->build_navigation( 'clubs' );

        $found_clubs = false;
        foreach ( $nav_vm->items as $item ) {
            if ( 'clubs' === $item->slug ) {
                $found_clubs = true;
                $this->assertFalse( $item->is_ported );
                $this->assertSame( 'https://example.test/wp-admin/admin.php?page=racketmanager-clubs', $item->url );
            }
        }
        $this->assertTrue( $found_clubs );
    }

    public function test_filters_out_unauthorized_navigation_sections(): void {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'user_can' )->willReturnCallback(
            fn( string $cap ) => 'racket_manager' === $cap
        );

        $presenter = $this->create_presenter( auth_guard: $auth_guard );

        $nav_vm = $presenter->build_navigation( 'competitions' );
        $slugs  = array_map( static fn( $item ) => $item->slug, $nav_vm->items );

        $this->assertContains( 'clubs', $slugs );
        $this->assertNotContains( 'settings', $slugs );
        $this->assertNotContains( 'import', $slugs );
    }

    public function test_renders_layout_shell(): void {
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'portal/layout',
                $this->callback(
                    fn( $vm ) => $vm instanceof Portal_Layout_View_Model
                        && 'Clubs Title' === $vm->title
                        && '<div>Content</div>' === $vm->content
                        && 'clubs' === $vm->active_section
                        && 'RacketManager' === $vm->site_name
                        && 'https://example.test/' === $vm->site_url
                        && 'https://example.test/manage/competitions' === $vm->portal_home_url
                )
            );

        $presenter = $this->create_presenter( $renderer );
        $presenter->render_layout( 'Clubs Title', '<div>Content</div>', 'clubs' );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState( false )]
    public function test_renders_layout_shell_with_site_name_from_get_bloginfo(): void {
        if ( ! function_exists( 'get_bloginfo' ) ) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval( 'function get_bloginfo( $show = "" ) { return "Leigh and Westcliff Tennis"; }' );
        }

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'portal/layout',
                $this->callback(
                    fn( $vm ) => $vm instanceof Portal_Layout_View_Model
                        && 'Leigh and Westcliff Tennis' === $vm->site_name
                        && 'https://example.test/' === $vm->site_url
                        && 'https://example.test/manage/competitions' === $vm->portal_home_url
                )
            );

        $presenter = $this->create_presenter( $renderer );
        $presenter->render_layout( 'Clubs Title', '<div>Content</div>', 'clubs' );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState( false )]
    public function test_renders_layout_shell_decodes_html_entities_in_site_name(): void {
        if ( ! function_exists( 'get_bloginfo' ) ) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval( 'function get_bloginfo( $show = "" ) { return "Leigh &amp; Westcliff Tennis"; }' );
        }

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'portal/layout',
                $this->callback(
                    fn( $vm ) => $vm instanceof Portal_Layout_View_Model
                        && 'Leigh & Westcliff Tennis' === $vm->site_name
                        && 'https://example.test/' === $vm->site_url
                        && 'https://example.test/manage/competitions' === $vm->portal_home_url
                )
            );

        $presenter = $this->create_presenter( $renderer );
        $presenter->render_layout( 'Clubs Title', '<div>Content</div>', 'clubs' );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState( false )]
    public function test_renders_layout_shell_falls_back_when_get_bloginfo_returns_empty_string(): void {
        if ( ! function_exists( 'get_bloginfo' ) ) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval( 'function get_bloginfo( $show = "" ) { return ""; }' );
        }

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'portal/layout',
                $this->callback(
                    fn( $vm ) => $vm instanceof Portal_Layout_View_Model
                        && 'RacketManager' === $vm->site_name
                        && 'https://example.test/' === $vm->site_url
                        && 'https://example.test/manage/competitions' === $vm->portal_home_url
                )
            );

        $presenter = $this->create_presenter( $renderer );
        $presenter->render_layout( 'Clubs Title', '<div>Content</div>', 'clubs' );
    }

    public function test_layout_view_model_constructor_compatibility_with_defaults(): void {
        $nav_vm = new Portal_Navigation_View_Model( [] );
        $vm     = new Portal_Layout_View_Model(
            'Test Title',
            '<p>Test Content</p>',
            'clubs',
            $nav_vm
        );

        $this->assertSame( 'Test Title', $vm->title );
        $this->assertSame( '<p>Test Content</p>', $vm->content );
        $this->assertSame( 'clubs', $vm->active_section );
        $this->assertSame( $nav_vm, $vm->navigation );
        $this->assertSame( '', $vm->user_name );
        $this->assertSame( '', $vm->logout_url );
        $this->assertSame( [], $vm->flash );
        $this->assertSame( '', $vm->site_name );
        $this->assertSame( '', $vm->site_url );
        $this->assertSame( '', $vm->portal_home_url );
    }

    public function test_layout_view_model_constructor_accepts_all_properties(): void {
        $nav_vm = new Portal_Navigation_View_Model( [] );
        $vm     = new Portal_Layout_View_Model(
            'Test Title',
            '<p>Test Content</p>',
            'clubs',
            $nav_vm,
            'John Doe',
            'https://example.test/logout',
            [ 'message' => 'Saved' ],
            'Custom Site',
            'https://example.test/',
            'https://example.test/manage/competitions'
        );

        $this->assertSame( 'Custom Site', $vm->site_name );
        $this->assertSame( 'https://example.test/', $vm->site_url );
        $this->assertSame( 'https://example.test/manage/competitions', $vm->portal_home_url );
        $this->assertSame( 'John Doe', $vm->user_name );
        $this->assertSame( 'https://example.test/logout', $vm->logout_url );
        $this->assertSame( [ 'message' => 'Saved' ], $vm->flash );
    }

    public function test_present_unauthorized_renders_access_denied(): void {
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->once() )
            ->method( 'render_to_string' )
            ->with( 'portal/unauthorized', $this->anything() )
            ->willReturn( '<p>Unauthorized</p>' );

        $renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'portal/layout',
                $this->callback(
                    fn( $vm ) => $vm instanceof Portal_Layout_View_Model
                        && 'Access Denied' === $vm->title
                        && '<p>Unauthorized</p>' === $vm->content
                )
            );

        $presenter = $this->create_presenter( $renderer );
        $presenter->present_unauthorized( 'settings' );
    }
}
