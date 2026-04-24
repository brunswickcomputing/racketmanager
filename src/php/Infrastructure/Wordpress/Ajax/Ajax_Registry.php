<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

/**
 * Central registry for WordPress AJAX actions.
 */
final class Ajax_Registry {

    /**
     * Register a standard AJAX action.
     *
     * @param string $action The action name (without racketmanager_ prefix).
     * @param callable $callback The callback for authenticated users.
     * @param bool $public Whether to also register for non-authenticated users.
     */
    public function register( string $action, callable $callback, bool $public = false ): void {
        add_action( "wp_ajax_racketmanager_$action", $callback );
        if ( $public ) {
            add_action( "wp_ajax_nopriv_racketmanager_$action", $callback );
        }
    }

    /**
     * Register an AJAX action with a separate callback for unauthenticated users.
     *
     * @param string $action The action name.
     * @param callable $callback The callback for authenticated users.
     * @param callable $unauthenticated_callback The callback for unauthenticated users.
     */
    public function register_with_unauthenticated( string $action, callable $callback, callable $unauthenticated_callback ): void {
        add_action( "wp_ajax_racketmanager_$action", $callback );
        add_action( "wp_ajax_nopriv_racketmanager_$action", $unauthenticated_callback );
    }
}
