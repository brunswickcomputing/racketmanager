<?php
/**
 * RacketManager standalone functions
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

/**
 * Send debug code to the JavaScript console
 *
 * @param object|array|string|int|null $data Optional message that will be sent the error_log before the backtrace.
 */
function debug_to_console( object|array|string|int|null $data ): void {
    if ( is_array( $data ) || is_object( $data ) ) {
        if ( is_array( $data ) ) {
            error_log( 'PHP: array' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        } else {
            error_log( 'PHP: object' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        if ( ! wp_doing_ajax() ) {
            echo "<script>console.log('PHP: " . wp_json_encode( $data ) . "');</script>";
        }
        error_log( 'PHP: "' . wp_json_encode( $data ) . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    } else {
        if ( ! wp_doing_ajax() ) {
            echo "<script>console.log( 'PHP: " . esc_html( $data ) . "' ) ;</script>";
        }
        error_log( 'PHP: "' . $data . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

/**
 * Send the output from a backtrace to the error_log
 *
 * @param string $message Optional message that will be sent the error_log before the backtrace.
 */
function log_trace( string $message = '' ): void {
    $trace = debug_backtrace(); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
    if ( $message ) {
        error_log( $message ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
    $caller        = array_shift( $trace );
    $function_name = $caller['function'];
    error_log( sprintf( '%s: Called from %s:%s', $function_name, $caller['file'], $caller['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    foreach ( $trace as $entry_id => $entry ) {
        $entry['file'] = $entry['file'] ?? '-';
        $entry['line'] = $entry['line'] ?? '-';
        if ( empty( $entry['class'] ) ) {
            error_log( sprintf( '%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        } else {
            error_log( sprintf( '%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
}

/**
 * Create formatted url
 */
function create_new_url_querystring(): void {
    add_rewrite_tag( '%competition_name%', '(.+?)' );
    add_rewrite_tag( '%competition_type%', '(.+?)' );
    add_rewrite_tag( '%round%', '(.+?)' );
    add_rewrite_tag( '%league_name%', '(.+?)' );
    add_rewrite_tag( '%league_id%', '([^/]*)' );
    add_rewrite_tag( '%match_id%', '([^/]*)' );
    add_rewrite_tag( '%season%', '([0-9]{4})' );
    add_rewrite_tag( '%match_day%', '([0-9]{1,2})' );
    add_rewrite_tag( '%team%', '(.+?)' );
    add_rewrite_tag( '%teamHome%', '(.+?)' );
    add_rewrite_tag( '%teamAway%', '(.+?)' );
    add_rewrite_tag( '%club_name%', '(.+?)' );
    add_rewrite_tag( '%match_date%', '(.+?)' );
    add_rewrite_tag( '%type%', '(.+?)' );
    add_rewrite_tag( '%tournament%', '(.+?)' );
    add_rewrite_tag( '%player_id%', '(.+?)' );
    add_rewrite_tag( '%id%', '(.+?)' );
    add_rewrite_tag( '%days%', '([0-9]{1,3})' );
    add_rewrite_tag( '%event%', '(.+?)' );
    add_rewrite_tag( '%draw%', '(.+?)' );
    add_rewrite_tag( '%tab%', '(.+?)' );
    add_rewrite_tag( '%player%', '(.+?)' );
    add_rewrite_tag( '%leg%', '([0-9]{1})' );
    add_rewrite_tag( '%action%', '(.+?)' );
    add_rewrite_tag( '%competition%', '(.+?)' );
    add_rewrite_tag( '%btm%', '([0-9]+)' );
    add_rewrite_tag( '%invoice%', '([0-9]+)' );
    add_rewrite_tag( '%age_group%', '(.+?)' );
}
add_action( 'init', 'Racketmanager\create_new_url_querystring' );

/**
 * Get the current page url
 */
function wp_get_current_url(): ?string {
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
    } else {
        return null;
    }
}
