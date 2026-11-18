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
        unset( $GLOBALS['wp_stubs_esc_html_callback'] );
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
                is_ported: true,
                group: 'Competitions'
            ),
            new Portal_Navigation_Item(
                slug: 'reports',
                title: 'Reports',
                url: 'https://example.test/wp-admin/admin.php?page=racketmanager-reports',
                is_active: false,
                is_ported: false,
                group: 'Administration'
            ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        self::assertStringContainsString( 'class="rm-portal-nav"', $html );
        self::assertStringContainsString( 'aria-label="Management Navigation"', $html );
        self::assertStringContainsString( 'class="rm-portal-nav__list rm-portal-nav__list--vertical"', $html );

        // Group headers and nested group lists
        self::assertStringContainsString( 'class="rm-portal-nav__group"', $html );
        self::assertStringContainsString( '<span id="rm-portal-nav-group-competitions" class="rm-portal-nav__group-title">Competitions</span>', $html );
        self::assertStringContainsString( '<ul class="rm-portal-nav__group-list" aria-labelledby="rm-portal-nav-group-competitions">', $html );
        self::assertStringContainsString( '<span id="rm-portal-nav-group-administration" class="rm-portal-nav__group-title">Administration</span>', $html );
        self::assertStringContainsString( '<ul class="rm-portal-nav__group-list" aria-labelledby="rm-portal-nav-group-administration">', $html );

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

    public function test_render_portal_navigation_template_renders_group_headers_and_skips_empty(): void {
        $items = [
            new Portal_Navigation_Item(
                slug: 'overview',
                title: 'Overview',
                url: 'https://example.test/manage/overview',
                is_active: true,
                is_ported: true,
                group: ''
            ),
            new Portal_Navigation_Item(
                slug: 'leagues',
                title: 'Leagues',
                url: 'https://example.test/manage/leagues',
                is_active: false,
                is_ported: true,
                group: 'Competitions'
            ),
            new Portal_Navigation_Item(
                slug: 'cups',
                title: 'Cups',
                url: 'https://example.test/manage/cups',
                is_active: false,
                is_ported: true,
                group: 'Competitions'
            ),
            new Portal_Navigation_Item(
                slug: 'clubs',
                title: 'Clubs',
                url: 'https://example.test/manage/clubs',
                is_active: false,
                is_ported: true,
                group: 'Organizations'
            ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        // Group headers for Competitions and Organizations must be present
        self::assertStringContainsString( '<span id="rm-portal-nav-group-competitions" class="rm-portal-nav__group-title">Competitions</span>', $html );
        self::assertStringContainsString( '<span id="rm-portal-nav-group-organizations" class="rm-portal-nav__group-title">Organizations</span>', $html );

        // Overview is ungrouped, so no empty group header should exist before Overview
        self::assertStringNotContainsString( '<span id="rm-portal-nav-group-', substr( $html, 0, (int) strpos( $html, 'https://example.test/manage/overview' ) ) );

        // Check sequential order of headers and items
        preg_match_all( '#class="rm-portal-nav__group-title">([^<]+)</span>#', $html, $matches );
        $rendered_groups = $matches[1];
        self::assertSame( [ 'Competitions', 'Organizations' ], $rendered_groups );

        // Confirm Leagues and Cups share the same Competitions group without repeating the header
        self::assertSame( 1, substr_count( $html, 'class="rm-portal-nav__group-title">Competitions</span>' ) );
    }

    public function test_render_portal_navigation_template_does_not_render_empty_headers_when_group_is_filtered_out(): void {
        // Simulating filtered list where all items belonging to 'Administration' or other groups were omitted
        $items = [
            new Portal_Navigation_Item(
                slug: 'overview',
                title: 'Overview',
                url: 'https://example.test/manage/overview',
                is_active: false,
                is_ported: true,
                group: ''
            ),
            new Portal_Navigation_Item(
                slug: 'results',
                title: 'Results',
                url: 'https://example.test/manage/results',
                is_active: true,
                is_ported: true,
                group: 'Audits & Operations'
            ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        // Only Audits & Operations header should be present, not Competitions, Organizations, or Administration
        self::assertStringContainsString( 'class="rm-portal-nav__group-title">Audits & Operations</span>', $html );
        self::assertStringNotContainsString( 'Competitions', $html );
        self::assertStringNotContainsString( 'Organizations', $html );
        self::assertStringNotContainsString( 'Administration', $html );

        // Check active state on results link
        self::assertStringContainsString( 'rm-portal-nav__item--active', $html );
        self::assertStringContainsString( 'aria-current="page"', $html );
        self::assertStringContainsString( 'https://example.test/manage/results', $html );
    }

    public function test_render_portal_navigation_template_escapes_group_title(): void {
        $GLOBALS['wp_stubs_esc_html_callback'] = fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );

        try {
            $items = [
                new Portal_Navigation_Item(
                    slug: 'custom',
                    title: 'Custom Item',
                    url: 'https://example.test/manage/custom',
                    is_active: false,
                    is_ported: true,
                    group: 'Finance & <Audit>'
                ),
            ];

            $nav_vm = new Portal_Navigation_View_Model( $items );
            $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

            self::assertStringContainsString( 'Finance &amp; &lt;Audit&gt;', $html );
            self::assertStringContainsString( 'id="rm-portal-nav-group-finance-audit"', $html );
        } finally {
            unset( $GLOBALS['wp_stubs_esc_html_callback'] );
        }
    }

    public function test_render_portal_navigation_template_merges_discontinuous_custom_sections_into_groups(): void {
        $items = [
            new Portal_Navigation_Item(
                slug: 'overview',
                title: 'Overview',
                url: 'https://example.test/manage/overview',
                is_active: false,
                is_ported: true,
                group: ''
            ),
            new Portal_Navigation_Item(
                slug: 'leagues',
                title: 'Leagues',
                url: 'https://example.test/manage/leagues',
                is_active: false,
                is_ported: true,
                group: 'Competitions'
            ),
            new Portal_Navigation_Item(
                slug: 'clubs',
                title: 'Clubs',
                url: 'https://example.test/manage/clubs',
                is_active: false,
                is_ported: true,
                group: 'Organizations'
            ),
            // Custom item registered later that targets 'Competitions'
            new Portal_Navigation_Item(
                slug: 'custom-comp',
                title: 'Custom Comp',
                url: 'https://example.test/manage/custom-comp',
                is_active: false,
                is_ported: true,
                group: 'Competitions'
            ),
            // Custom item with no group (empty string)
            new Portal_Navigation_Item(
                slug: 'custom-ungrouped',
                title: 'Custom Ungrouped',
                url: 'https://example.test/manage/custom-ungrouped',
                is_active: false,
                is_ported: true,
                group: ''
            ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        // Competitions header should appear only ONCE
        self::assertSame( 1, substr_count( $html, 'class="rm-portal-nav__group-title">Competitions</span>' ) );

        // Both leagues and custom-comp should be under the Competitions group list
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-competitions">(.*?)</ul>#s', $html, $comp_group );
        self::assertNotEmpty( $comp_group );
        self::assertStringContainsString( 'https://example.test/manage/leagues', $comp_group[1] );
        self::assertStringContainsString( 'https://example.test/manage/custom-comp', $comp_group[1] );

        // Custom ungrouped item should NOT be inside any group-list
        self::assertStringNotContainsString( 'https://example.test/manage/custom-ungrouped', $comp_group[1] );
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-organizations">(.*?)</ul>#s', $html, $org_group );
        self::assertNotEmpty( $org_group );
        self::assertStringNotContainsString( 'https://example.test/manage/custom-ungrouped', $org_group[1] );

        // Verify custom-ungrouped is rendered at top-level list
        self::assertStringContainsString( 'https://example.test/manage/custom-ungrouped', $html );
    }

    public function test_render_portal_navigation_template_full_default_hierarchy(): void {
        $items = [
            new Portal_Navigation_Item( 'overview', 'Overview', 'https://example.test/manage/overview', true, true, '' ),
            new Portal_Navigation_Item( 'leagues', 'Leagues', 'https://example.test/manage/leagues', false, true, 'Competitions' ),
            new Portal_Navigation_Item( 'cups', 'Cups', 'https://example.test/manage/cups', false, true, 'Competitions' ),
            new Portal_Navigation_Item( 'tournaments', 'Tournaments', 'https://example.test/manage/tournaments', false, true, 'Competitions' ),
            new Portal_Navigation_Item( 'clubs', 'Clubs', 'https://example.test/manage/clubs', false, true, 'Organizations' ),
            new Portal_Navigation_Item( 'players', 'Players', 'https://example.test/manage/players', false, true, 'Organizations' ),
            new Portal_Navigation_Item( 'results', 'Results', 'https://example.test/manage/results', false, true, 'Audits & Operations' ),
            new Portal_Navigation_Item( 'seasons', 'Seasons', 'https://example.test/manage/seasons', false, true, 'Administration' ),
            new Portal_Navigation_Item( 'finances', 'Finances', 'https://example.test/manage/finances', false, true, 'Administration' ),
            new Portal_Navigation_Item( 'settings', 'Settings', 'https://example.test/manage/settings', false, true, 'Administration' ),
            new Portal_Navigation_Item( 'import', 'Import', 'https://example.test/manage/import', false, true, 'Administration' ),
            new Portal_Navigation_Item( 'documentation', 'Documentation', 'https://example.test/manage/documentation', false, true, 'Administration' ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        // Verify headers sit adjacent to their exact links
        // Competitions group list contains leagues, cups, tournaments
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-competitions">(.*?)</ul>#s', $html, $comp_list );
        self::assertNotEmpty( $comp_list );
        preg_match_all( '#href="https://example\.test/manage/([^"]+)"#', $comp_list[1], $comp_slugs );
        self::assertSame( [ 'leagues', 'cups', 'tournaments' ], $comp_slugs[1] );

        // Organizations group list contains clubs, players
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-organizations">(.*?)</ul>#s', $html, $org_list );
        self::assertNotEmpty( $org_list );
        preg_match_all( '#href="https://example\.test/manage/([^"]+)"#', $org_list[1], $org_slugs );
        self::assertSame( [ 'clubs', 'players' ], $org_slugs[1] );

        // Audits & Operations group list contains results
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-audits-operations">(.*?)</ul>#s', $html, $audit_list );
        self::assertNotEmpty( $audit_list );
        preg_match_all( '#href="https://example\.test/manage/([^"]+)"#', $audit_list[1], $audit_slugs );
        self::assertSame( [ 'results' ], $audit_slugs[1] );

        // Administration group list contains seasons, finances, settings, import, documentation
        preg_match( '#<ul class="rm-portal-nav__group-list"[^>]*aria-labelledby="rm-portal-nav-group-administration">(.*?)</ul>#s', $html, $admin_list );
        self::assertNotEmpty( $admin_list );
        preg_match_all( '#href="https://example\.test/manage/([^"]+)"#', $admin_list[1], $admin_slugs );
        self::assertSame( [ 'seasons', 'finances', 'settings', 'import', 'documentation' ], $admin_slugs[1] );

        // Verify Overview is outside of any group list: it appears before the first group container
        self::assertMatchesRegularExpression( '#<ul class="rm-portal-nav__list[^"]*">\s*<li class="rm-portal-nav__item[^"]*">\s*<a href="https://example\.test/manage/overview"[^>]*>.*?</a>\s*</li>\s*<li class="rm-portal-nav__group">#s', $html );
    }

    public function test_render_portal_navigation_template_generates_unique_ids_for_similarly_slugged_groups(): void {
        $items = [
            new Portal_Navigation_Item( 'item1', 'Item 1', 'https://example.test/manage/item1', false, true, 'Audits & Operations' ),
            new Portal_Navigation_Item( 'item2', 'Item 2', 'https://example.test/manage/item2', false, true, 'Audits Operations' ),
        ];

        $nav_vm = new Portal_Navigation_View_Model( $items );
        $html   = $this->renderer->render_to_string( 'portal/navigation', $nav_vm );

        self::assertStringContainsString( 'id="rm-portal-nav-group-audits-operations"', $html );
        self::assertStringContainsString( 'aria-labelledby="rm-portal-nav-group-audits-operations"', $html );
        self::assertStringContainsString( 'id="rm-portal-nav-group-audits-operations-2"', $html );
        self::assertStringContainsString( 'aria-labelledby="rm-portal-nav-group-audits-operations-2"', $html );
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

        try {
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
        } finally {
            unset( $GLOBALS['wp_stubs_esc_html_callback'] );
        }
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
        self::assertStringContainsString( 'href="/manage/overview"', $html );
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
