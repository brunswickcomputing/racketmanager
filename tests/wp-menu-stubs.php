<?php
/**
 * Recording stubs for WordPress admin menu functions used by unit tests.
 *
 * Loaded with require_once by tests that assert on menu registration. Each function is
 * guarded so it never redeclares a definition supplied by another stub file.
 *
 * @package Racketmanager
 */

declare( strict_types=1 );

if ( ! defined( 'RACKETMANAGER_PLUGIN_BASENAME' ) ) {
    define( 'RACKETMANAGER_PLUGIN_BASENAME', 'racketmanager/racketmanager.php' );
}

if ( ! function_exists( 'add_menu_page' ) ) {
    /**
     * Records a top-level menu registration in $GLOBALS['wp_stubs_added_menu_pages'].
     *
     * @param string $page_title Page title.
     * @param string $menu_title Menu title.
     * @param string $capability Required capability.
     * @param string $menu_slug  Menu slug.
     * @param mixed  $callback   Page callback.
     * @param string $icon_url   Menu icon URL.
     * @param int|null $position Menu position.
     * @return string Hook suffix (or $GLOBALS['wp_stubs_add_menu_page_return'] when set).
     */
    function add_menu_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        string $icon_url = '',
        ?int $position = null
    ): string {
        $GLOBALS['wp_stubs_added_menu_pages'][] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'callback'   => $callback,
            'icon_url'   => $icon_url,
            'position'   => $position,
        ];

        if ( isset( $GLOBALS['wp_stubs_add_menu_page_return'] ) ) {
            return $GLOBALS['wp_stubs_add_menu_page_return'];
        }

        return 'toplevel_page_' . $menu_slug;
    }
}

if ( ! function_exists( 'add_submenu_page' ) ) {
    /**
     * Records a submenu registration in $GLOBALS['wp_stubs_added_submenu_pages'].
     *
     * A per-slug return override is read from $GLOBALS['wp_stubs_add_submenu_page_returns'],
     * then a global override from $GLOBALS['wp_stubs_add_submenu_page_return'].
     *
     * @param string $parent_slug Parent menu slug.
     * @param string $page_title  Page title.
     * @param string $menu_title  Menu title.
     * @param string $capability  Required capability.
     * @param string $menu_slug   Menu slug.
     * @param mixed  $callback    Page callback.
     * @param int|null $position  Menu position.
     * @return mixed Hook suffix, or the configured override.
     */
    function add_submenu_page(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        ?int $position = null
    ) {
        $GLOBALS['wp_stubs_added_submenu_pages'][] = [
            'parent_slug' => $parent_slug,
            'page_title'  => $page_title,
            'menu_title'  => $menu_title,
            'capability'  => $capability,
            'menu_slug'   => $menu_slug,
            'callback'    => $callback,
            'position'    => $position,
        ];

        if ( isset( $GLOBALS['wp_stubs_add_submenu_page_returns'][ $menu_slug ] ) ) {
            return $GLOBALS['wp_stubs_add_submenu_page_returns'][ $menu_slug ];
        }

        if ( isset( $GLOBALS['wp_stubs_add_submenu_page_return'] ) ) {
            return $GLOBALS['wp_stubs_add_submenu_page_return'];
        }

        return 'racketmanager_page_' . $menu_slug;
    }
}

if ( ! function_exists( 'get_the_author_meta' ) ) {
    /**
     * Retrieve metadata for the specified user or author.
     *
     * @param string   $field   The meta field name.
     * @param int|null $user_id The user ID.
     * @return mixed
     */
    function get_the_author_meta( $field = '', $user_id = null ) {
        if ( null !== $user_id && isset( $GLOBALS['wp_stubs_user_meta'][ (int) $user_id ][ (string) $field ] ) ) {
            $val = $GLOBALS['wp_stubs_user_meta'][ (int) $user_id ][ (string) $field ];
            return is_array( $val ) ? ( $val[0] ?? '' ) : $val;
        }

        if ( isset( $GLOBALS['wp_stubs_author_meta'][ (string) $field ] ) ) {
            return $GLOBALS['wp_stubs_author_meta'][ (string) $field ];
        }

        return '';
    }
}
