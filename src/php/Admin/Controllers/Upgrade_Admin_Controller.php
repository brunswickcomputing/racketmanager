<?php
declare( strict_types=1 );
/**
 * Upgrade Admin Controller
 *
 * Handles upgrade page display and execution.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use LogicException;
use Racketmanager\Admin\View_Models\Upgrade_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Upgrade;
use Throwable;
use wpdb;

/**
 * Class Upgrade_Admin_Controller
 *
 * Coordinates database upgrade requests with security assertions and PRG flow.
 */
final readonly class Upgrade_Admin_Controller {

    /**
     * @param RacketManager          $racketmanager Main plugin instance.
     * @param Action_Guard_Interface $action_guard  Action guard service.
     * @param wpdb|null              $wpdb          Database connection, defaults to $GLOBALS['wpdb'].
     */
    public function __construct(
        private RacketManager $racketmanager,
        private Action_Guard_Interface $action_guard,
        private ?wpdb $wpdb = null,
    ) {
    }

    /**
     * Handle display or execution of the database upgrade.
     *
     * @param array<string, mixed> $query Current query arguments.
     * @param array<string, mixed> $post  Current POST payload.
     *
     * @return array{view_model?: Upgrade_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     * @throws LogicException When wpdb instance is unavailable for upgrade execution.
     */
    public function upgrade_page( array $query, array $post ): array {
        if ( isset( $post['doUpgrade'] ) ) {
            try {
                $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_upgrade', 'manage_racketmanager' );
            } catch ( Invalid_Status_Exception $e ) {
                return array(
                    'redirect'     => Admin_Redirect_Url_Builder::upgrade_screen( $query ),
                    'message'      => $e->getMessage(),
                    'message_type' => true,
                );
            }

            return $this->run_upgrade( $query );
        }

        $this->action_guard->assert_capability( 'manage_racketmanager' );

        $options   = $this->racketmanager->options;
        $installed = $options['dbversion'] ?? null;

        return array(
            'view_model' => new Upgrade_Page_View_Model(
                installed: is_scalar( $installed ) ? strval( $installed ) : null,
                latest_version: defined( 'RACKETMANAGER_DBVERSION' ) ? RACKETMANAGER_DBVERSION : '',
            ),
        );
    }

    /**
     * Run the upgrade, keeping its progress output and reporting failures.
     *
     * @param array<string, mixed> $query Current query arguments.
     *
     * @return array{redirect: string, message: string, message_type: bool, flash_data: array<string, mixed>}
     * @throws LogicException When wpdb instance is unavailable.
     */
    private function run_upgrade( array $query ): array {
        $db = $this->wpdb ?? ( isset( $GLOBALS['wpdb'] ) && $GLOBALS['wpdb'] instanceof wpdb ? $GLOBALS['wpdb'] : null );
        if ( null === $db ) {
            throw new LogicException( 'wpdb instance is required to run upgrade.' );
        }

        $failure             = null;
        $initial_ezsql_count = isset( $GLOBALS['EZSQL_ERROR'] ) && is_array( $GLOBALS['EZSQL_ERROR'] ) ? count( $GLOBALS['EZSQL_ERROR'] ) : 0;

        ob_start();
        try {
            new Upgrade( $this->racketmanager, $db )->run();
        } catch ( Throwable $e ) {
            $failure = $e->getMessage();
        }
        $output = strval( ob_get_clean() );

        if ( null === $failure ) {
            $failure = $this->detect_failure( $db, $output, $initial_ezsql_count );
        }

        $lines   = self::extract_lines( $output );
        $details = implode( '', array_map( static fn( string $line ): string => '<p>' . self::escape_shortcode_text( $line ) . '</p>', $lines ) );

        if ( null !== $failure ) {
            $safe_failure = self::escape_shortcode_text( $failure );
            $message      = '<p>' . esc_html__( 'Upgrade failed', 'racketmanager' ) . '</p>' . $details . '<p>' . $safe_failure . '</p>';
        } else {
            $message = esc_html__( 'Upgrade successful', 'racketmanager' ) . $details;
        }

        return array(
            'redirect'     => Admin_Redirect_Url_Builder::upgrade_screen( $query ),
            'message'      => $message,
            'message_type' => null !== $failure,
            'flash_data'   => array(
                'upgrade_log' => $lines,
            ),
        );
    }

    /**
     * Detect database error message from wpdb, EZSQL_ERROR, or captured output.
     *
     * @param wpdb   $db                  Database connection.
     * @param string $output              Captured upgrade output.
     * @param int    $initial_ezsql_count Number of EZSQL errors present prior to upgrade run.
     *
     * @return string|null Error string if failure detected, null otherwise.
     */
    private function detect_failure( wpdb $db, string $output, int $initial_ezsql_count ): ?string {
        $db_error = trim( $db->last_error ?? '' );
        if ( '' !== $db_error ) {
            $failure = $db_error;
        } elseif ( isset( $GLOBALS['EZSQL_ERROR'] ) && is_array( $GLOBALS['EZSQL_ERROR'] ) && count( $GLOBALS['EZSQL_ERROR'] ) > $initial_ezsql_count ) {
            $last_ez = end( $GLOBALS['EZSQL_ERROR'] );
            $failure = is_array( $last_ez ) && ! empty( $last_ez['error_str'] ) ? strval( $last_ez['error_str'] ) : __( 'WordPress database error', 'racketmanager' );
        } elseif ( preg_match( '/(?:wpdberror|WordPress database error)[^\[]*\[(.*?)]/is', $output, $matches ) ) {
            $failure = html_entity_decode( wp_strip_all_tags( $matches[1] ), ENT_QUOTES );
        } elseif ( str_contains( $output, 'wpdberror' ) || str_contains( $output, 'WordPress database error' ) ) {
            $failure = __( 'WordPress database error', 'racketmanager' );
        } else {
            $failure = null;
        }

        return $failure;
    }

    /**
     * Extract cleaned text lines from captured output.
     *
     * @param string $output Raw captured output.
     *
     * @return array<int, string>
     */
    private static function extract_lines( string $output ): array {
        $text  = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br />', '<br>' ), "\n", $output ) ), ENT_QUOTES );
        $lines = array_filter( array_map( 'trim', explode( "\n", $text ) ), static fn( string $line ): bool => '' !== $line );

        return array_values( $lines );
    }

    /**
     * Safely escape text for inclusion in a shortcode attribute without breaking shortcode parsing or HTML.
     *
     * @param string $text Plain or decoded text.
     *
     * @return string
     */
    private static function escape_shortcode_text( string $text ): string {
        return str_replace(
            array( '\\', '[', ']' ),
            array( '&#92;', '&#91;', '&#93;' ),
            esc_html( $text )
        );
    }
}
