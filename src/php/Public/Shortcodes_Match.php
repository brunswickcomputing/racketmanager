<?php
/**
 * Shortcodes_Match API: Shortcodes_Match class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;

/**
 * Backward-compatible facade for the match shortcodes.
 *
 * All behaviour lives in Matches_Shortcode_Adapter; this class only keeps the legacy public
 * signatures for external callers and delegates one way to the container-resolved adapter.
 *
 * @deprecated Use Matches_Shortcode_Adapter.
 */
class Shortcodes_Match extends Shortcodes {
    /**
     * [score id=ID team=x opponent=x home_away=x template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_score( array $atts ): false|string {
        return $this->adapter()->show_score( $atts );
    }

    /**
     * [match-header id=ID edit=x template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_match_header( array $atts ): string {
        return $this->adapter()->show_match_header( $atts );
    }

    /**
     * [match-detail id=ID player=ID template=X]
     *
     * @param array $atts shortcode attributes.
     */
    public function show_match_detail( array $atts ): string {
        return $this->adapter()->show_match_detail( $atts );
    }

    private function adapter(): Matches_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'matches_shortcode_adapter' );
    }
}
