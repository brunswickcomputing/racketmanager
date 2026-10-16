<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

/**
 * Trait to manage snapshot and restoration of WordPress globals and superglobals.
 * Preserves the exact initial presence/absence of global variables.
 */
trait Manages_Wordpress_Globals {

    private array $superglobals_snapshot = [];
    private array $globals_snapshot      = [];

    /**
     * List of managed global keys in $GLOBALS.
     *
     * @var list<string>
     */
    private static array $managed_global_keys = [
        'racketmanager',
        'racketmanager_instance',
        'wp',
        'wpdb',
        'query_vars',
        'wp_mail_calls',
        'wp_stubs_current_user_can',
        'wp_stubs_options',
        'wp_stubs_league_teams',
        'wp_stubs_leagues',
        'wp_stubs_is_lta_number_required',
        'wp_stubs_get_current_user_id',
        'wp_stubs_is_user_logged_in',
        'wp_stubs_current_user_id',
        'wp_stubs_clubs',
        'wp_stubs_events',
        'wp_stubs_teams',
        'wp_stubs_matches',
        'wp_stubs_players',
        'wp_stubs_rubbers',
        'wp_stubs_messages',
        'wp_stubs_last_wp_die',
        'wp_stubs_rest_do_request_return',
        'wp_stubs_last_rest_request',
        'wp_stubs_check_admin_referer_return',
        'wp_stubs_last_check_admin_referer',
        'wp_stubs_wp_verify_nonce_return',
        'wp_filesystem',
        'wp_stubs_filesystem_method',
        'wp_stubs_wp_filesystem_return',
        'wp_stubs_cache_deleted',
        'wp_stubs_cache',
        'wp_stubs_cache_enabled',
        'wp_stubs_cache_mode',
        'event',
        'league',
        'post',
        'match',
        'wp_query',
    ];

    protected function backup_globals(): void {
        $this->superglobals_snapshot = [
            'GET'     => $_GET,
            'POST'    => $_POST,
            'FILES'   => $_FILES,
            'REQUEST' => $_REQUEST,
            'SERVER'  => $_SERVER,
        ];

        $this->globals_snapshot = [];
        foreach ( self::$managed_global_keys as $key ) {
            if ( array_key_exists( $key, $GLOBALS ) ) {
                $this->globals_snapshot[ $key ] = [
                    'exists' => true,
                    'value'  => $GLOBALS[ $key ],
                ];
            } else {
                $this->globals_snapshot[ $key ] = [
                    'exists' => false,
                ];
            }
        }
    }

    protected function restore_globals(): void {
        if ( isset( $this->superglobals_snapshot['GET'] ) ) {
            $_GET = $this->superglobals_snapshot['GET'];
        }
        if ( isset( $this->superglobals_snapshot['POST'] ) ) {
            $_POST = $this->superglobals_snapshot['POST'];
        }
        if ( isset( $this->superglobals_snapshot['FILES'] ) ) {
            $_FILES = $this->superglobals_snapshot['FILES'];
        }
        if ( isset( $this->superglobals_snapshot['REQUEST'] ) ) {
            $_REQUEST = $this->superglobals_snapshot['REQUEST'];
        }
        if ( isset( $this->superglobals_snapshot['SERVER'] ) ) {
            $_SERVER = $this->superglobals_snapshot['SERVER'];
        }

        foreach ( $this->globals_snapshot as $key => $state ) {
            if ( $state['exists'] ) {
                $GLOBALS[ $key ] = $state['value'];
            } else {
                unset( $GLOBALS[ $key ] );
            }
        }

        $this->superglobals_snapshot = [];
        $this->globals_snapshot      = [];
    }
}
