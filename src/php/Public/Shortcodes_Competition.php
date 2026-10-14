<?php
declare( strict_types=1 );

/**
 * Shortcodes_Competition API: Shortcodes_Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Competition
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;

/**
 * Backward-compatible facade for the competition shortcodes.
 *
 * All behaviour lives in Competition_Shortcode_Adapter; this class only keeps the legacy public
 * signatures for external callers and delegates one way to the container-resolved adapter.
 *
 * @deprecated Use Competition_Shortcode_Adapter.
 */
class Shortcodes_Competition extends Shortcodes {
    /**
     * Show competitions shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competitions( array $atts ): string {
        return $this->adapter()->show_competitions( $atts );
    }

    /**
     * Show competition shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition( array $atts ): string {
        return $this->adapter()->show_competition( $atts );
    }

    /**
     * Show competition overview shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_overview( array $atts ): string {
        return $this->adapter()->show_competition_overview( $atts );
    }

    /**
     * Show competition events shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_events( array $atts ): string {
        return $this->adapter()->show_competition_events( $atts );
    }

    /**
     * Show competition teams shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_teams( array $atts ): string {
        return $this->adapter()->show_competition_teams( $atts );
    }

    /**
     * Show competition clubs shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_clubs( array $atts ): string {
        return $this->adapter()->show_competition_clubs( $atts );
    }

    /**
     * Show competition players shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_players( array $atts ): string {
        return $this->adapter()->show_competition_players( $atts );
    }

    /**
     * Show competition winners shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_winners( array $atts ): string {
        return $this->adapter()->show_competition_winners( $atts );
    }

    /**
     * Show competition entry shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_entry( array $atts ): string {
        return $this->adapter()->show_competition_entry( $atts );
    }

    /**
     * Show competition entry payment shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_competition_entry_payment( array $atts ): string {
        return $this->adapter()->show_competition_entry_payment( $atts );
    }

    /**
     * Show competition entry payment complete shortcode.
     */
    public function show_competition_entry_payment_complete(): string {
        return $this->adapter()->show_competition_entry_payment_complete();
    }

    /**
     * Show event dropdown shortcode.
     *
     * @param array $atts attributes.
     */
    public function show_dropdown( array $atts ): string {
        return $this->adapter()->show_dropdown( $atts );
    }

    /**
     * Check if club selection is available.
     *
     * @param object $competition competition object.
     * @param false|int $club_id club ID.
     * @return object|int|bool|array
     */
    public function club_selection_available( object $competition, false|int $club_id = false ): object|int|bool|array {
        return $this->adapter()->club_selection_available( $competition, $club_id );
    }

    private function adapter(): Competition_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'competition_shortcode_adapter' );
    }
}
