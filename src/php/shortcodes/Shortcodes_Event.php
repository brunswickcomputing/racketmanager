<?php
/**
 * Shortcodes_Event API: Shortcodes_Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Event
 */

namespace Racketmanager\shortcodes;

use Racketmanager\Player;
use Racketmanager\util\Util;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_player;
use function Racketmanager\get_tab;
use function Racketmanager\get_team;
use function Racketmanager\un_seo_url;

/**
 * Class to implement the Shortcodes_Event object
 */
class Shortcodes_Event extends Shortcodes {
    /**
     * Show Event
     *
     * [event_id=ID season=X template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string
     */
    public function show_event( array $atts ): string {
        $args   = shortcode_atts(
            array(
                'id'     => 0,
                'season' => false,
            ),
            $atts
        );
        $id     = $args['id'];
        $season = $args['season'];
        $event  = null;
        if ( $id ) {
            $event = get_event( $id );
        } else {
            $event_id = get_query_var( 'event' );
            if ( $event_id ) {
                $event_id = str_replace( '-', ' ', $event_id );
                $event = get_event( $event_id, 'name' );
            }
        }
        if ( $event ) {
            $event->set_season( $season );
            if ( empty( $event->current_season ) ) {
                $msg = __( 'Season not found for event', 'racketmanager' );
            } else {
                $season  = $event->current_season['name'];
                $seasons = $event->seasons;
                $tab = get_tab();
                $filename = 'event';
                return $this->load_template(
                    $filename,
                    array(
                        'event'       => $event,
                        'seasons'     => $seasons,
                        'curr_season' => $season,
                        'tab'         => $tab,
                    )
                );
            }
        } else {
            $msg = $this->event_not_found;
        }
        return $this->return_error( $msg );
    }
    /**
     * Function to display event standings
     *
     * @param array $atts shortcode attributes.
     *
     * @return string - the content
     */
    public function show_event_standings( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->leagues = $event->get_leagues();
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'standings-' . $template : 'standings';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display event draws
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_event_draw( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'draws-' . $template : 'draws';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display event matches
     *
     * @param array $atts
     * @return string
     */
    public function show_event_matches( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display event clubs
     *
     * @param array $atts
     * @return string
     */
    public function show_event_clubs( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display event teams
     *
     * @param array $atts
     * @return string
     */
    public function show_event_teams( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'teams-' . $template : 'teams';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display event players
     *
     * @param array $atts
     * @return string
     */
    public function show_event_players( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display partner selection
     *
     * @param array $atts
     * @return string
     */
    public function show_event_partner( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'partner-' . $template : 'partner';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display team order players
     *
     * @param array $atts
     * @return string
     */
    public function show_team_order_players( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $template = $args['template'];
        $season   = $args['season'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $event->set_season( $season );
        $filename = ( ! empty( $template ) ) ? 'team-order-players-' . $template : 'team-order-players';
        return $this->load_template(
            $filename,
            array(
                'event' => $event,
            ),
            'event'
        );
    }
    /**
     * Function to display a league list dropdown for an event
     *
     * @param array $atts
     * @return string
     */
    public function show_dropdown( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
                'season'   => false,
            ),
            $atts
        );
        $event_id = $args['id'];
        $event    = get_event( $event_id );
        if ( ! $event ) {
            $msg = $this->event_not_found;
            return $this->return_error( $msg );
        }
        $club_name = get_query_var( 'club_name' );
        $league    = get_query_var( 'league' );
        $league    = un_seo_url( $league );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        $leagues   = $event->get_leagues();
        return $this->load_template(
            'dropdown',
            array(
                'club'    => $club,
                'leagues' => $leagues,
                'league'  => $league,
            ),
            'event'
        );
    }
}
