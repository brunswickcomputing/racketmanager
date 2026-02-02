<?php
/**
 * Util_Messages API: Util_Messages class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Util
 */

namespace Racketmanager\Util;

class Util_Messages {
    /**
     * Set the Club not found message
     *
     * @param $club_id
     *
     * @return string
     */
    public static function club_not_found( $club_id = null ): string {
        if ( $club_id ) {
            return sprintf( __( 'Club with ID %s not found', 'racketmanager' ), $club_id );
        } else {
            return __( 'Club not found', 'racketmanager' );
        }
    }

    /**
     * Set the Club shortcode not found message
     *
     * @param $club_id
     *
     * @return string
     */
    public static function club_shortcode_not_found( $club_id = null ): string {
        return sprintf( __( 'Club with shortcode %s not found', 'racketmanager' ), $club_id );
    }

    /**
     * Set the Team not found message
     *
     * @param $team_id
     *
     * @return string
     */
    public static function team_not_found( $team_id = null ): string {
        if ( $team_id ) {
            return sprintf( __( 'Team with ID %s not found', 'racketmanager' ), $team_id );
        } else {
            return __( 'Team not found', 'racketmanager' );
        }
    }

    /**
     * Set the Player not found message
     *
     * @param $player_id
     *
     * @return string
     */
    public static function player_not_found( $player_id = null ): string {
        if ( $player_id ) {
            return sprintf( __( 'Player with ID %s not found', 'racketmanager' ), $player_id );
        } else {
            return __( 'Player not found', 'racketmanager' );
        }
    }

    /**
     * Set the Competition not found message
     *
     * @param $competition_id
     *
     * @return string
     */
    public static function competition_not_found( $competition_id = null ): string {
        if ( $competition_id ) {
            return sprintf( __( 'Competition with ID %s not found', 'racketmanager' ), $competition_id );
        } else {
            return __( 'Competition not found', 'racketmanager' );
        }
    }

    /**
     * Set the Competition not updated message
     *
     * @return string
     */
    public static function competition_not_updated(): string {
        return __( 'Failed to update competition', 'racketmanager' );
    }

    /**
     * Set the Event not found message
     *
     * @param $event_id
     *
     * @return string
     */
    public static function event_not_found( $event_id = null ): string {
        if ( $event_id ) {
            return sprintf( __( 'Event with ID %s not found', 'racketmanager' ), $event_id );
        } else {
            return __( 'Event not found', 'racketmanager' );
        }
    }

    /**
     * Set the Season not found message
     *
     * @param ?int $season
     *
     * @return string
     */
    public static function season_not_found( ?int $season = null ): string {
        if ( $season ) {
            return sprintf( __( 'Season %d not found', 'racketmanager' ), $season );
        } else {
            return __( 'Season not found', 'racketmanager' );
        }
    }

    /**
     * Set the invalid team id message
     *
     * @return string
     */
    public static function invalid_team_id(): string {
        return __( 'Invalid team ID', 'racketmanager' );
    }

    /**
     * Set the invalid team type message
     *
     * @return string
     */
    public static function invalid_team_type(): string {
        return __( 'Invalid team type', 'racketmanager' );
    }

    public static function charge_not_found( ?int $charge_id = null ): string {
        if ( $charge_id ) {
            return sprintf( __( 'Charge %d not found', 'racketmanager' ), $charge_id );
        } else {
            return __( 'Charge not found', 'racketmanager' );
        }
    }

    public static function charge_has_invoices( ?int $charge_id = null ): string {
        if ( $charge_id ) {
            return sprintf( __( 'Charge %d still has invoices', 'racketmanager' ), $charge_id );
        } else {
            return __( 'Charge still has invoices', 'racketmanager' );
        }
    }

    public static function charge_deleted( ?int $charge_id = null ): string {
        if ( $charge_id ) {
            return sprintf( __( 'Charge %d deleted', 'racketmanager' ), $charge_id );
        } else {
            return __( 'Charge deleted', 'racketmanager' );
        }
    }

    public static function charge_not_deleted( ?int $charge_id = null ): string {
        if ( $charge_id ) {
            return sprintf( __( 'Charge %d not deleted', 'racketmanager' ), $charge_id );
        } else {
            return __( 'Charge not deleted', 'racketmanager' );
        }
    }

    public static function no_updates(): string {
        return __( 'No updates', 'racketmanager' );
    }

    public static function invoice_not_found( ?int $invoice_id = null ): string {
        if ( $invoice_id ) {
            return sprintf( __( 'Invoice with ID %s not found', 'racketmanager' ), $invoice_id );
        } else {
            return __( 'Invoice not found', 'racketmanager' );
        }
    }

    public static function no_billing_details(): string {
        return __( 'No billing details', 'racketmanager' );
    }

    public static function missing_action_parameter(): string {
        return __( 'Action parameter missing', 'racketmanager' );
    }

    public static function invalid_parameter( bool $status ): string {
        return sprintf( __( 'Status %s is an invalid value', 'racketmanager' ), $status );
    }

    public static function tournament_not_found( ?int $tournament_id ): string {
        if ( $tournament_id ) {
            return sprintf( __( 'Tournament with ID %s not found', 'racketmanager' ), $tournament_id );
        } else {
            return __( 'Tournament not found', 'racketmanager' );
        }
    }

    public static function secretary_email_not_found( string $string ): string {
        return sprintf( __( '%s secretary email not found', 'racketmanager' ), ucfirst( $string ) );
    }

    public static function purchase_order_unchanged(): string {
        return __( 'PO not changedg', 'racketmanager' );
    }

    public static function invalid_notification_type( string $type ): string {
        return sprintf( __( '%s is not valid as a notification type', 'racketmanager' ), $type );
    }

}
