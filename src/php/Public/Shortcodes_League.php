<?php
declare( strict_types=1 );

/**
 * Shortcodes_League API: Shortcodes_League class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/League
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;

/**
 * Backward-compatible facade for League shortcodes.
 *
 * All shortcode logic is implemented in League_Shortcode_Adapter and its Query Handlers.
 * This class preserves legacy public signatures and delegates to the container adapter.
 *
 * @deprecated Use League_Shortcode_Adapter.
 */
class Shortcodes_League extends Shortcodes {
    /**
     * Display Championship
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_championship( array $atts ): false|string {
        return $this->adapter()->show_championship( $atts );
    }

    /**
     * Display Archive
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_archive( array $atts ): false|string {
        return $this->adapter()->show_archive( $atts );
    }

    /**
     * Display Standings table
     *
     * @param array $atts shortcode attributes.
     * @param boolean $widget widget indicator.
     * @return false|string
     */
    public function show_standings( array $atts, bool $widget = false ): false|string {
        return $this->adapter()->show_standings( $atts, $widget );
    }

    /**
     * Display Crosstable
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_crosstable( array $atts ): false|string {
        return $this->adapter()->show_crosstable( $atts );
    }

    /**
     * Display League Matches
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_matches( array $atts ): string {
        return $this->adapter()->show_matches( $atts );
    }

    /**
     * Display single match
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_match( array $atts ): string {
        return $this->adapter()->show_match( $atts );
    }

    /**
     * Display Team list
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_teams( array $atts ): string {
        return $this->adapter()->show_teams( $atts );
    }

    /**
     * Display league Players
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_league_players( array $atts ): false|string {
        return $this->adapter()->show_league_players( $atts );
    }

    /**
     * Display season dropdown
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_season_dropdown( array $atts ): false|string {
        return $this->adapter()->show_season_dropdown( $atts );
    }

    /**
     * Display match dropdown
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_match_dropdown( array $atts ): false|string {
        return $this->adapter()->show_match_dropdown( $atts );
    }

    /**
     * Display last 5 matches
     *
     * @param array $atts shortcode attributes.
     * @return false|string
     */
    public function show_last_5( array $atts ): false|string {
        return $this->adapter()->show_last_5( $atts );
    }

    private function adapter(): League_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'league_shortcode_adapter' );
    }
}
