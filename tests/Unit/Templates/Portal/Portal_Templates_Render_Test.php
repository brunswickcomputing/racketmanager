<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Layout_View_Model;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_Item;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_View_Model;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Portal_Templates_Render_Test extends TestCase {
    use Creates_View_Renderer;
    use Manages_Wordpress_Globals;

    private View_Renderer_Interface $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->renderer = $this->create_view_renderer();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_render_portal_navigation_template(): void {
        $items = [
            new Portal_Navigation_Item(
                slug: 'competitions',
                title: 'Competitions',
                url: 'https://example.test/manage/competitions',
                is_active: true,
                is_ported: true
            ),
            new Portal_Navigation_Item(
                slug: 'reports',
                title: 'Reports',
                url: 'https://example.test/wp-admin/admin.php?page=racketmanager-reports',
                is_active: false,
                is_ported: false
            ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        self::assertStringContainsString( 'class="rm-portal-nav"', $html );
        self::assertStringContainsString( 'aria-label="Management Navigation"', $html );
        self::assertStringContainsString( 'class="rm-portal-nav__list rm-portal-nav__list--vertical"', $html );

        // Active item assertions
        self::assertStringContainsString( 'rm-portal-nav__item--active', $html );
        self::assertStringContainsString( 'aria-current="page"', $html );
        self::assertStringContainsString( 'https://example.test/manage/competitions', $html );
        self::assertStringContainsString( 'Competitions', $html );

        // Unported item and badge assertions
        self::assertStringContainsString( 'https://example.test/wp-admin/admin.php?page=racketmanager-reports', $html );
        self::assertStringContainsString( 'Reports', $html );
        self::assertStringContainsString( 'class="rm-portal-nav__badge"', $html );
        self::assertStringContainsString( 'aria-hidden="true">wp-admin</span>', $html );
        self::assertStringContainsString( 'class="screen-reader-text">Opens in wp-admin</span>', $html );
        self::assertStringNotContainsString( 'aria-label="Opens in wp-admin"', $html );
        self::assertStringContainsString( 'wp-admin', $html );
    }

    public function test_render_portal_layout_template(): void {
        $nav_items = [
            new Portal_Navigation_Item(
                slug: 'competitions',
                title: 'Competitions',
                url: 'https://example.test/manage/competitions',
                is_active: true,
                is_ported: true
            ),
        ];
        $nav_vm    = new Portal_Navigation_View_Model( $nav_items );

        $layout_vm = new Portal_Layout_View_Model(
            title: 'Manage Competitions',
            content: '<div class="inner-screen-content">Hello Admin</div>',
            active_section: 'competitions',
            navigation: $nav_vm,
            user_name: 'Jane Official',
            logout_url: 'https://example.test/wp-login.php?action=logout',
            flash: [
                'message'      => 'Changes saved successfully',
                'message_type' => 'success',
            ],
            site_name: 'Leigh & Westcliff Tennis',
            site_url: 'https://example.test/',
            portal_home_url: 'https://example.test/manage/competitions'
        );

        $html = $this->renderer->render_to_string( 'portal/layout', $layout_vm );

        // Skip link
        self::assertStringContainsString( 'href="#rm-portal-content"', $html );
        self::assertStringContainsString( 'Skip to content', $html );

        // Document Title
        self::assertStringContainsString( '<title>Manage Competitions &mdash; Leigh & Westcliff Tennis</title>', $html );

        // Sticky Header & Brand
        self::assertStringContainsString( 'class="rm-portal-header"', $html );
        self::assertStringContainsString( 'class="rm-portal-header__brand"', $html );
        self::assertStringContainsString( 'href="https://example.test/manage/competitions"', $html );
        self::assertStringContainsString( 'Manage - Leigh & Westcliff Tennis', $html );

        // Header Actions: Site Link, User Name, Logout
        self::assertStringContainsString( 'class="rm-portal-header__actions rm-portal-header__user"', $html );
        self::assertStringContainsString( 'class="rm-portal-header__site-link"', $html );
        self::assertStringContainsString( 'href="https://example.test/"', $html );
        self::assertStringContainsString( 'View Site', $html );
        self::assertStringContainsString( 'class="rm-portal-header__username"', $html );
        self::assertStringContainsString( 'Jane Official', $html );
        self::assertStringContainsString( 'class="rm-portal-header__logout"', $html );
        self::assertStringContainsString( 'href="https://example.test/wp-login.php?action=logout"', $html );
        self::assertStringContainsString( 'Log Out', $html );

        // Two-Column Body Layout (Sidebar + Main)
        self::assertStringContainsString( 'class="rm-portal-body"', $html );
        self::assertStringContainsString( '<aside class="rm-portal-sidebar"', $html );
        self::assertStringContainsString( '<main class="rm-portal-main" id="rm-portal-content">', $html );

        // Sidebar renders navigation
        self::assertStringContainsString( 'class="rm-portal-nav"', $html );
        self::assertStringContainsString( 'Competitions', $html );

        // Flash message
        self::assertStringContainsString( 'class="rm-alert rm-alert--success"', $html );
        self::assertStringContainsString( 'Changes saved successfully', $html );

        // Pre-rendered content
        self::assertStringContainsString( '<div class="inner-screen-content">Hello Admin</div>', $html );

        // Footer
        self::assertStringContainsString( 'class="rm-portal-footer"', $html );
        self::assertStringContainsString( 'Leigh & Westcliff Tennis. All rights reserved.', $html );
    }

    public function test_render_portal_layout_template_escapes_special_characters_without_double_escaping(): void {
        $GLOBALS['wp_stubs_esc_html_callback'] = fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );

        $nav_vm    = new Portal_Navigation_View_Model( [] );
        $layout_vm = new Portal_Layout_View_Model(
            title: 'Manage <Clubs>',
            content: '<p>Content</p>',
            active_section: '',
            navigation: $nav_vm,
            user_name: 'Jane <Admin>',
            logout_url: 'https://example.test/wp-login.php?action=logout&amp;foo=bar',
            flash: [],
            site_name: 'Leigh & Westcliff Tennis',
            site_url: 'https://example.test/?a=1&b=2',
            portal_home_url: 'https://example.test/manage/competitions'
        );

        $html = $this->renderer->render_to_string( 'portal/layout', $layout_vm );

        // Verify HTML escaping of special characters
        self::assertStringContainsString( '<title>Manage &lt;Clubs&gt; &mdash; Leigh &amp; Westcliff Tennis</title>', $html );
        self::assertStringContainsString( 'Manage - Leigh &amp; Westcliff Tennis', $html );
        self::assertStringContainsString( 'Jane &lt;Admin&gt;', $html );
        self::assertStringContainsString( 'Leigh &amp; Westcliff Tennis. All rights reserved.', $html );
        // Ensure no double escaping of &amp;
        self::assertStringNotContainsString( '&amp;amp;', $html );
    }

    public function test_render_portal_layout_template_with_empty_optional_fields_and_error_flash(): void {
        $nav_vm    = new Portal_Navigation_View_Model( [] );
        $layout_vm = new Portal_Layout_View_Model(
            title: 'Simple Title',
            content: '<p>Content</p>',
            active_section: '',
            navigation: $nav_vm,
            user_name: '',
            logout_url: '',
            flash: [
                'message'      => 'Something went wrong',
                'message_type' => 'error',
            ],
            site_name: '',
            site_url: '',
            portal_home_url: ''
        );

        $html = $this->renderer->render_to_string( 'portal/layout', $layout_vm );

        // Defaults when URLs/site name are empty
        self::assertStringContainsString( 'href="/manage/competitions"', $html );
        self::assertStringContainsString( 'Manage - RacketManager', $html );
        self::assertStringContainsString( 'href="/"', $html );

        // No username or logout elements when empty
        self::assertStringNotContainsString( 'class="rm-portal-header__username"', $html );
        self::assertStringNotContainsString( 'class="rm-portal-header__logout"', $html );

        // Error flash notice class
        self::assertStringContainsString( 'class="rm-alert rm-alert--error"', $html );
        self::assertStringContainsString( 'Something went wrong', $html );
    }
}
