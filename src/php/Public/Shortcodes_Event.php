<?php
declare( strict_types=1 );

/**
 * Shortcodes_Event API: Shortcodes_Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Event
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;

/**
 * Backward-compatible facade for Event shortcodes.
 *
 * All shortcode logic is implemented in Event_Shortcode_Adapter and its Query Handlers.
 * This class preserves legacy public signatures and delegates to the container adapter.
 *
 * @deprecated Use Event_Shortcode_Adapter.
 */
class Shortcodes_Event extends Shortcodes {
    /**
     * Show Event
     *
     * [event id=ID season=X]
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event( array $atts ): false|string {
        return $this->adapter()->show_event( $atts );
    }

    /**
     * Function to display event standings
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_standings( array $atts ): false|string {
        return $this->adapter()->show_event_standings( $atts );
    }

    /**
     * Function to display event draw
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_draw( array $atts ): false|string {
        return $this->adapter()->show_event_draw( $atts );
    }

    /**
     * Function to display event matches
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_matches( array $atts ): false|string {
        return $this->adapter()->show_event_matches( $atts );
    }

    /**
     * Function to display event Clubs
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_clubs( array $atts ): false|string {
        return $this->adapter()->show_event_clubs( $atts );
    }

    /**
     * Function to display event teams
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_teams( array $atts ): false|string {
        return $this->adapter()->show_event_teams( $atts );
    }

    /**
     * Function to display event Players
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_players( array $atts ): false|string {
        return $this->adapter()->show_event_players( $atts );
    }

    /**
     * Function to display event partner
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_partner( array $atts ): false|string {
        return $this->adapter()->show_event_partner( $atts );
    }

    /**
     * Function to display event Players
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_event_team_matches( array $atts ): false|string {
        return $this->adapter()->show_event_team_matches( $atts );
    }

    /**
     * Function to display team order players
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_team_order_players( array $atts ): false|string {
        return $this->adapter()->show_team_order_players( $atts );
    }

    /**
     * Function to display league dropdown
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_dropdown( array $atts ): false|string {
        return $this->adapter()->show_dropdown( $atts );
    }

    private function adapter(): Event_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'event_shortcode_adapter' );
    }
}
