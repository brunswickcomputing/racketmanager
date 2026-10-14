<?php
declare( strict_types=1 );

/**
 * Shortcodes_Tournament API: Shortcodes_Tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Tournament
 */

namespace Racketmanager\Public;

use Racketmanager\Domain\Tournament;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;

/**
 * Backward-compatible facade for Tournament shortcodes.
 *
 * All shortcode logic is implemented in Tournament_Shortcode_Adapter and its Query Handlers.
 * This class preserves legacy public signatures and delegates to the container adapter.
 *
 * @deprecated Use Tournament_Shortcode_Adapter.
 */
class Shortcodes_Tournament extends Shortcodes {
    /**
     * Show tournament
     *
     * [tournament tournament=X template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament( array $atts = [] ): string {
        return $this->adapter()->show_tournament( $atts );
    }

    /**
     * Show tournament overview
     *
     * [tournament-overview id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_overview( array $atts = [] ): string {
        return $this->adapter()->show_tournament_overview( $atts );
    }

    /**
     * Show tournament events
     *
     * [tournament-events id=ID events=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_events( array $atts = [] ): string {
        return $this->adapter()->show_tournament_events( $atts );
    }

    /**
     * Show tournament event
     *
     * @param Tournament $tournament
     * @param int|string|null $event_id
     * @return string
     */
    public function show_tournament_event( Tournament $tournament, int|string|null $event_id ): string {
        return $this->adapter()->show_tournament_events( [
            'id'     => $tournament->get_id(),
            'events' => $event_id,
        ] );
    }

    /**
     * Show draws for a tournament
     *
     * [tournament-draws id=ID draws=X template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_draws( array $atts = [] ): string {
        return $this->adapter()->show_draws( $atts );
    }

    /**
     * Show tournament draw
     *
     * @param Tournament $tournament
     * @param int|string|null $draw_id
     * @return string
     */
    public function show_draw( Tournament $tournament, int|string|null $draw_id ): string {
        return $this->adapter()->show_draws( [
            'id'    => $tournament->get_id(),
            'draws' => $draw_id,
        ] );
    }

    /**
     * Show tournament players
     *
     * [tournament-players id=ID players=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_players( array $atts = [] ): string {
        return $this->adapter()->show_tournament_players( $atts );
    }

    /**
     * Show tournament player
     *
     * @param Tournament $tournament
     * @param int|string|null $player_id
     * @return string
     */
    public function show_tournament_player( Tournament $tournament, int|string|null $player_id ): string {
        return $this->adapter()->show_tournament_players( [
            'id'      => $tournament->get_id(),
            'players' => $player_id,
        ] );
    }

    /**
     * Show tournament winners
     *
     * [tournament-winners id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_winners( array $atts = [] ): string {
        return $this->adapter()->show_tournament_winners( $atts );
    }

    /**
     * Show tournament matches
     *
     * [tournament-matches id=ID match_date=X template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_matches( array $atts = [] ): string {
        return $this->adapter()->show_tournament_matches( $atts );
    }

    /**
     * Show tournament single match
     *
     * [tournament-match]
     *
     * @return string
     */
    public function show_tournament_match(): string {
        return $this->adapter()->show_tournament_match();
    }

    /**
     * Show order of play
     *
     * [orderofplay id=ID competition_id=X season=X template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_order_of_play( array $atts = [] ): string {
        return $this->adapter()->show_order_of_play( $atts );
    }

    /**
     * Show latest tournament
     *
     * [latest-tournament age_group=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_latest_tournament( array $atts = [] ): string {
        return $this->adapter()->show_latest_tournament( $atts );
    }

    /**
     * Show tournament withdrawal modal
     *
     * [tournament-withdrawal id=ID player_id=X modal=X]
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_withdrawal_modal( array $atts = [] ): string {
        return $this->adapter()->show_tournament_withdrawal_modal( $atts );
    }

    /**
     * Show tournament withdrawal alias
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_withdrawal( array $atts = [] ): string {
        return $this->adapter()->show_tournament_withdrawal( $atts );
    }

    private function adapter(): Tournament_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'tournament_shortcode_adapter' );
    }
}
