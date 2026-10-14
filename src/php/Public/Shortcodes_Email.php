<?php
/**
 * Shortcodes_Email API: Shortcodes_Email class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;

/**
 * Backward-compatible facade for the email and notification shortcodes.
 *
 * All behaviour lives in Email_Shortcode_Adapter; this class only keeps the legacy public
 * signatures for external callers and delegates one way to the container-resolved adapter.
 *
 * @deprecated Use Email_Shortcode_Adapter.
 */
class Shortcodes_Email extends Shortcodes {
    /**
     * [match-notification match=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_match_notification( array $atts ): string {
        return $this->adapter()->show_match_notification( $atts );
    }

    /**
     * [result-notification match=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_result_notification( array $atts ): string {
        return $this->adapter()->show_result_notification( $atts );
    }

    /**
     * [result-notification-captain match=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_captain_result_notification( array $atts ): string {
        return $this->adapter()->show_captain_result_notification( $atts );
    }

    /**
     * Alias for show_captain_result_notification.
     *
     * @param array $atts shortcode attributes.
     */
    public function show_result_notification_captain( array $atts ): string {
        return $this->adapter()->show_result_notification_captain( $atts );
    }

    /**
     * [result-outstanding-notification match=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_result_outstanding_notification( array $atts ): string {
        return $this->adapter()->show_result_outstanding_notification( $atts );
    }

    /**
     * [club-player-notification club=X template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_club_player_notification( array $atts ): string {
        return $this->adapter()->show_club_player_notification( $atts );
    }

    /**
     * [match_date_change_notification match=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_match_date_change_notification( array $atts ): string {
        return $this->adapter()->show_match_date_change_notification( $atts );
    }

    /**
     * [withdrawn-team team=ID league=ID season=X template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_team_withdrawn( array $atts ): string {
        return $this->adapter()->show_team_withdrawn( $atts );
    }

    /**
     * Alias for show_team_withdrawn.
     *
     * @param array $atts shortcode attributes.
     */
    public function show_withdrawn_team( array $atts ): string {
        return $this->adapter()->show_withdrawn_team( $atts );
    }

    /**
     * [withdrawn-team-match is_tournament=X event=X template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_withdrawn_team_match( array $atts ): string {
        return $this->adapter()->show_withdrawn_team_match( $atts );
    }

    /**
     * [event-constitution id=ID season=X template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_event_constitution( array $atts ): string {
        return $this->adapter()->show_event_constitution( $atts );
    }

    private function adapter(): Email_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'email_shortcode_adapter' );
    }
}
