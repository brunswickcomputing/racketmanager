<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Portal;

use PHPUnit\Framework\TestCase;

final class Portal_Style_Contract_Test extends TestCase {
    private string $source_css;

    protected function setUp(): void {
        parent::setUp();

        $plugin_root = dirname( __DIR__, 4 );
        $source_path = $plugin_root . '/css/style.css';

        self::assertFileExists( $source_path, 'Source CSS file css/style.css must exist.' );

        $source_content = file_get_contents( $source_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        self::assertIsString( $source_content );

        $this->source_css = $source_content;
    }

    /**
     * Extracts CSS declarations for all rule blocks matching a selector.
     *
     * @return array<int, string>
     */
    private function get_rule_blocks( string $selector, ?string $scope = null ): array {
        $haystack = $scope ?? $this->source_css;
        $blocks   = [];

        if ( preg_match_all( '/([^{}]+)\{([^{}]*)\}/s', $haystack, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $match ) {
                $raw_selectors = preg_replace( '/\/\*.*?\*\//s', '', $match[1] );
                $selectors     = array_map( 'trim', explode( ',', (string) $raw_selectors ) );
                if ( in_array( trim( $selector ), $selectors, true ) ) {
                    $blocks[] = $match[2];
                }
            }
        }

        if ( [] === $blocks ) {
            self::fail( "CSS rule block for selector '{$selector}' was not found in stylesheet." );
        }

        return $blocks;
    }

    /**
     * Extracts CSS declarations within the first matching selector rule block.
     */
    private function get_rule_block( string $selector, ?string $scope = null ): string {
        $blocks = $this->get_rule_blocks( $selector, $scope );
        return $blocks[0];
    }

    /**
     * Extracts the content of an @media query block within the portal section using balanced brace parsing.
     */
    private function get_media_block( string $query_fragment ): string {
        $portal_start = strpos( $this->source_css, '@section: Management Portal' );
        $search_from  = false !== $portal_start ? $portal_start : 0;

        $pos = strpos( $this->source_css, $query_fragment, $search_from );
        self::assertNotFalse( $pos, "Media query containing '{$query_fragment}' not found in css/style.css." );

        $open_brace = strpos( $this->source_css, '{', $pos );
        self::assertNotFalse( $open_brace, "Opening brace for media query '{$query_fragment}' not found." );

        $depth  = 0;
        $length = strlen( $this->source_css );
        $start  = $open_brace;
        $end    = -1;

        for ( $i = $open_brace; $i < $length; ++$i ) {
            if ( '{' === $this->source_css[ $i ] ) {
                ++$depth;
            } elseif ( '}' === $this->source_css[ $i ] ) {
                --$depth;
                if ( 0 === $depth ) {
                    $end = $i;
                    break;
                }
            }
        }

        self::assertGreaterThan( $start, $end, "Closing brace for media query '{$query_fragment}' not found." );
        return substr( $this->source_css, $start + 1, $end - $start - 1 );
    }

    public function test_css_contains_portal_shell_and_wrapper(): void {
        $shell_block = $this->get_rule_block( 'body.racketmanager-portal-shell' );
        self::assertStringContainsString( '--rm-portal-header-height: 58px;', $shell_block );
        self::assertStringContainsString( '--rm-portal-adminbar-height: 0px;', $shell_block );

        $adminbar_block = $this->get_rule_block( 'body.racketmanager-portal-shell.admin-bar' );
        self::assertStringContainsString( '--rm-portal-adminbar-height: 32px;', $adminbar_block );

        $wrapper_block = $this->get_rule_block( '.rm-portal-wrapper' );
        self::assertStringContainsString( 'display: flex;', $wrapper_block );
        self::assertStringContainsString( 'min-height: 100vh;', $wrapper_block );
    }

    public function test_css_contains_accessible_screen_reader_text_and_skip_link(): void {
        $sr_block = $this->get_rule_block( '.racketmanager-portal-shell .screen-reader-text' );
        self::assertStringContainsString( 'clip-path: inset(50%);', $sr_block );
        self::assertStringContainsString( 'position: absolute;', $sr_block );
        self::assertStringContainsString( 'overflow: hidden;', $sr_block );
        self::assertStringContainsString( 'word-wrap: normal;', $sr_block );
        self::assertStringNotContainsString( '!important', $sr_block );

        $skip_block = $this->get_rule_block( '.racketmanager-portal-shell .rm-portal-skip-link:focus' );
        self::assertStringContainsString( 'clip: auto;', $skip_block );
        self::assertStringContainsString( 'clip-path: none;', $skip_block );
        self::assertStringContainsString( 'color: #0369a1;', $skip_block );
        self::assertStringContainsString( 'border: 2px solid #0369a1;', $skip_block );
        self::assertStringContainsString( 'z-index: 100000;', $skip_block );
        self::assertStringNotContainsString( '!important', $skip_block );
    }

    public function test_css_contains_sticky_header_with_admin_bar_offsets(): void {
        $header_block = $this->get_rule_block( '.rm-portal-header' );
        self::assertStringContainsString( 'position: sticky;', $header_block );
        self::assertStringContainsString( 'z-index: 1020;', $header_block );
        self::assertStringContainsString( 'top: var(--rm-portal-adminbar-height, 0);', $header_block );

        $admin_header = $this->get_rule_block( 'body.racketmanager-portal-shell.admin-bar .rm-portal-header' );
        self::assertStringContainsString( 'top: var(--rm-portal-adminbar-height, 32px);', $admin_header );

        $brand_block = $this->get_rule_block( '.rm-portal-header__brand' );
        self::assertStringContainsString( 'color: #ffffff;', $brand_block );
        self::assertStringContainsString( 'text-decoration: none;', $brand_block );

        $actions_block = $this->get_rule_block( '.rm-portal-header__actions' );
        self::assertStringContainsString( 'display: flex;', $actions_block );
        self::assertStringContainsString( 'align-items: center;', $actions_block );

        $site_link = $this->get_rule_block( '.rm-portal-header__site-link' );
        self::assertStringContainsString( 'color: #94a3b8;', $site_link );

        $site_link_hover = $this->get_rule_block( '.rm-portal-header__site-link:hover' );
        self::assertStringContainsString( 'background-color: #334155;', $site_link_hover );

        $username = $this->get_rule_block( '.rm-portal-header__username' );
        self::assertStringContainsString( 'font-weight: 600;', $username );

        $logout = $this->get_rule_block( '.rm-portal-header__logout' );
        self::assertStringContainsString( 'color: #f87171;', $logout );

        $logout_hover = $this->get_rule_block( '.rm-portal-header__logout:hover' );
        self::assertStringContainsString( 'background-color: #334155;', $logout_hover );
    }

    public function test_css_contains_two_column_body_and_sidebar_rules(): void {
        $body_block = $this->get_rule_block( '.rm-portal-body' );
        self::assertStringContainsString( 'display: flex;', $body_block );

        $sidebar_block = $this->get_rule_block( '.rm-portal-sidebar' );
        self::assertStringContainsString( 'width: 240px;', $sidebar_block );
        self::assertStringContainsString( 'flex: 0 0 240px;', $sidebar_block );

        $media_769       = $this->get_media_block( 'min-width: 769px' );
        $desktop_sidebar = $this->get_rule_block( '.rm-portal-sidebar', $media_769 );
        self::assertStringContainsString( 'position: sticky;', $desktop_sidebar );
        self::assertStringContainsString( 'top: calc(var(--rm-portal-header-height, 58px) + var(--rm-portal-adminbar-height, 0px));', $desktop_sidebar );
        self::assertStringContainsString( 'overflow-y: auto;', $desktop_sidebar );
    }

    public function test_css_contains_navigation_and_unported_badge_rules(): void {
        $vert_list = $this->get_rule_block( '.rm-portal-nav__list--vertical' );
        self::assertStringContainsString( 'flex-direction: column;', $vert_list );

        $nav_link = $this->get_rule_block( '.rm-portal-nav__link' );
        self::assertStringContainsString( 'display: flex;', $nav_link );
        self::assertStringContainsString( 'justify-content: space-between;', $nav_link );

        $active_link = $this->get_rule_block( '.rm-portal-nav__link.is-active' );
        self::assertStringContainsString( 'color: #0369a1;', $active_link );
        self::assertStringContainsString( 'border-left-color: #0284c7;', $active_link );
        self::assertStringContainsString( 'background-color: #e0f2fe;', $active_link );

        $badge = $this->get_rule_block( '.rm-portal-nav__badge' );
        self::assertStringContainsString( 'color: #475569;', $badge );
        self::assertStringContainsString( 'background-color: #e2e8f0;', $badge );
        self::assertStringContainsString( 'text-transform: uppercase;', $badge );

        $active_badge = $this->get_rule_block( '.rm-portal-nav__link.is-active .rm-portal-nav__badge' );
        self::assertStringContainsString( 'color: #075985;', $active_badge );
        self::assertStringContainsString( 'background-color: #bae6fd;', $active_badge );
    }

    public function test_css_contains_main_content_alerts_and_footer(): void {
        $main = $this->get_rule_block( '.rm-portal-main' );
        self::assertStringContainsString( 'flex: 1 1 auto;', $main );
        self::assertStringContainsString( 'min-width: 0;', $main );

        $success = $this->get_rule_block( '.rm-portal-main .rm-alert--success' );
        self::assertStringContainsString( 'background-color: #f0fdf4;', $success );

        $error = $this->get_rule_block( '.rm-portal-main .rm-alert--error' );
        self::assertStringContainsString( 'background-color: #fef2f2;', $error );

        $footer = $this->get_rule_block( '.rm-portal-footer' );
        self::assertStringContainsString( 'border-top: 1px solid #e2e8f0;', $footer );
    }

    public function test_css_contains_responsive_media_queries(): void {
        $media_768      = $this->get_media_block( 'max-width: 768px' );
        $mobile_body    = $this->get_rule_block( '.rm-portal-body', $media_768 );
        $mobile_sidebar = $this->get_rule_block( '.rm-portal-sidebar', $media_768 );
        $mobile_brand   = $this->get_rule_block( '.rm-portal-header__brand', $media_768 );

        self::assertStringContainsString( 'flex-direction: column;', $mobile_body );
        self::assertStringContainsString( 'overflow-x: auto;', $mobile_sidebar );
        self::assertStringContainsString( 'white-space: normal;', $mobile_brand );
        self::assertStringContainsString( 'overflow-wrap: anywhere;', $mobile_brand );

        $media_782 = $this->get_media_block( 'max-width: 782px' );
        $admin_782 = $this->get_rule_block( 'body.racketmanager-portal-shell.admin-bar', $media_782 );
        self::assertStringContainsString( '--rm-portal-adminbar-height: 46px;', $admin_782 );

        $media_600 = $this->get_media_block( 'max-width: 600px' );
        $admin_600 = $this->get_rule_block( 'body.racketmanager-portal-shell.admin-bar', $media_600 );
        self::assertStringContainsString( '--rm-portal-adminbar-height: 0px;', $admin_600 );
    }
}
