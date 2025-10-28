<?php
/**
 * Shortcodes_email API: Shortcodes_email class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager\shortcodes;

use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\seo_url;

/**
 * Class to implement shortcode functions for emails
 *
 * NOTE: This class content mirrors the legacy include version for BC.
 */
class Shortcodes_Email extends Shortcodes {
    /**
     * Function to show match notification
     *
     *    [match-notification id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_match_notification( array $atts ): string {
        global $racketmanager;
        $args            = shortcode_atts(
            array(
                'match'           => '',
                'template'        => '',
                'tournament'      => false,
                'competition'     => '',
                'emailfrom'       => '',
                'round'           => '',
                'competition_type' => '',
            ),
            $atts
        );
        $match            = $args['match'];
        $template         = $args['template'];
        $tournament       = $args['tournament'];
        $competition      = $args['competition'];
        $email_from       = $args['emailfrom'];
        $round            = $args['round'];
        $competition_type = $args['competition_type'];
        $organisation     = $racketmanager->site_name;
        $match            = get_match( $match );

        $teams = array(
            'home' => new stdClass(),
            'away' => new stdClass(),
        );

        $home_dtls       = array();
        $away_dtls       = array();
        $tournament_link = '';
        $match_link      = '';
        $cup_link        = '';
        $draw_link       = '';
        $rules_link      = $racketmanager->site_url . '/rules/' . $competition_type . '-rules/';
        if ( 'tournament' === $competition_type ) {
            $tournament      = get_tournament( $tournament );
            $tournament_link = '<a href="' . $racketmanager->site_url . $tournament->link . '">' . $tournament->name . '</a>';
            $draw_link       = '<a href="' . $racketmanager->site_url .  $tournament->link . 'draw/' . seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
            $match_link      = $racketmanager->site_url . $tournament->link . '/match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
            if ( substr( $match->league->type, 1, 1 ) === 'D' ) {
                $teams['home']->title = __( 'Home Players', 'racketmanager' );
                $teams['away']->title = __( 'Away Players', 'racketmanager' );
                $home_dtls['title']   = 'Home Players';
                $away_dtls['title']   = 'Away Players';
            } else {
                $teams['home']->title = __( 'Home Player', 'racketmanager' );
                $teams['away']->title = __( 'Away Player', 'racketmanager' );
                $home_dtls['title']   = 'Home Player';
                $away_dtls['title']   = 'Away Player';
            }
        } elseif ( 'cup' === $competition_type ) {
            $cup_link   = '<a href="' . $racketmanager->site_url . '/cups/' . seo_url( $match->league->title ) . '/' . $match->season . '/">' . $match->league->title . '</a>';
            $match_link = $racketmanager->site_url . $match->link;
            if ( ! empty( $match->leg ) ) {
                $match_link .= 'leg-' . $match->leg . '/';
            }
            $template             = 'cup';
            $teams['home']->title = __( 'Home Team', 'racketmanager' );
            $teams['away']->title = __( 'Away Team', 'racketmanager' );
            $home_dtls['title']   = 'Home Team';
            $away_dtls['title']   = 'Away Team';
        }
        if ( 'P' === $match->teams['home']->team_type ) {
            foreach ( $match->teams['home']->players as $player ) {
                $teams['home']->player[] = $player;
            }
            foreach ( $match->teams['away']->players as $player ) {
                $teams['away']->player[] = $player;
            }
        } else {
            $teams['home']->captain           = $match->teams['home']->captain;
            $teams['home']->captain_email     = $match->teams['home']->contactemail;
            $teams['home']->captain_tel       = $match->teams['home']->contactno;
            $teams['home']->matchDay          = $match->teams['home']->match_day;
            $teams['home']->matchTime         = $match->teams['home']->match_time;
            $teams['away']->captain           = $match->teams['away']->captain;
            $teams['away']->captain_email     = $match->teams['away']->contactemail;
            $teams['away']->captain_tel       = $match->teams['away']->contactno;
            $teams['away']->matchDay          = $match->teams['away']->match_day;
            $teams['away']->matchTime         = $match->teams['away']->match_time;
        }

        $filename = ( ! empty( $template ) ) ? 'match-notification-' . $template : 'match-notification';

        return $this->load_template(
            $filename,
            array(
                'tournament'      => $tournament,
                'competition'     => $competition,
                'match'           => $match,
                'home_dtls'       => $home_dtls,
                'away_dtls'       => $away_dtls,
                'round'           => $round,
                'organisation'    => $organisation,
                'email_from'      => $email_from,
                'teams'           => $teams,
                'tournament_link' => $tournament_link,
                'draw_link'       => $draw_link,
                'action_url'      => $match_link,
                'rules_link'      => $rules_link,
                'cup_link'        => $cup_link,
            ),
            'email'
        );
    }
    /**
     * Function to show match confirmation
     * (kept for parity if present in legacy; can be added similarly)
     */
}
