<?php
/**
 * Shortcodes_Club API: Shortcodes_Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesClub
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;

/**
 * Backward-compatible facade for the club shortcodes.
 *
 * All behaviour lives in Club_Shortcode_Adapter; this class only keeps the legacy public
 * signatures for external callers and delegates one way to the container-resolved adapter.
 *
 * @deprecated Use Club_Shortcode_Adapter.
 */
class Shortcodes_Club extends Shortcodes {
    /**
     * [clubs template=X]
     *
     * @param array $atts attributes.
     */
    public function show_clubs( array $atts ): string {
        return $this->adapter()->show_clubs( $atts );
    }

    /**
     * [club template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club( array $atts ): false|string {
        return $this->adapter()->show_club( $atts );
    }

    /**
     * [club-players template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_players( array $atts ): string {
        return $this->adapter()->show_club_players( $atts );
    }

    /**
     * [club-competitions template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_competitions( array $atts ): string {
        return $this->adapter()->show_club_competitions( $atts );
    }

    /**
     * [club-team template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_team( array $atts ): string {
        return $this->adapter()->show_club_team( $atts );
    }

    /**
     * [club-event template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_event( array $atts ): string {
        return $this->adapter()->show_club_event( $atts );
    }

    /**
     * [club-invoices template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_invoices( array $atts ): string {
        return $this->adapter()->show_club_invoices( $atts );
    }

    /**
     * [team-edit id=team_ID event_id=ID modal=X template=X]
     *
     * @param array $atts attributes.
     */
    public function show_team_edit_modal( array $atts ): string {
        return $this->adapter()->show_team_edit_modal( $atts );
    }

    /**
     * [club-roles template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_roles( array $atts ): string {
        return $this->adapter()->show_club_roles( $atts );
    }

    /**
     * [club-role-modal id=role_ID modal=X template=X]
     *
     * @param array $atts attributes.
     */
    public function show_club_role_modal( array $atts ): string {
        return $this->adapter()->show_club_role_modal( $atts );
    }

    private function adapter(): Club_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'club_shortcode_adapter' );
    }
}
