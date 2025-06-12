<?php
/**
 * Racketmanager_Shortcodes_Match API: Shortcodes_Match class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Racketmanager_Shortcodes_Match object
 */
class Racketmanager_Shortcodes_Match extends RacketManager_Shortcodes {
    public string $not_played;
    public string $retired_player;
    public string $not_played_no_opponent;
    /**
     * Initialize shortcodes
     */
    public function __construct() {
	    add_shortcode( 'match-option', array( &$this, 'show_match_option_modal' ) );
        add_shortcode( 'match-status', array( &$this, 'show_match_status_modal' ) );
        add_shortcode( 'rubber-status', array( &$this, 'show_rubber_status_modal' ) );
        $this->not_played             = __( 'Not played', 'racketmanager' );
        $this->retired_player         = __( 'Retired - %s', 'racketmanager' );
        $this->not_played_no_opponent = __( 'Match not played - %s did not show', 'racketmanager' );
    }
    /**
     * Function to display match option modal
     *
     *  [match-option match_id=ID option=x modal=x template=X]
     *
     * @param array $atts shortcode attributes.
     * @return false|string content
     */
    public function show_match_option_modal( array $atts ): false|string {
        $args      = shortcode_atts(
            array(
                'match_id' => 0,
                'option'   => null,
                'modal'    => null,
                'template' => '',
            ),
            $atts
        );
        $match_id = $args['match_id'];
        $option   = $args['option'];
        $template = $args['template'];
        $modal    = $args['modal'];
		$valid    = true;
		$msg      = null;
        $match    = get_match( $match_id );
        if ( $match ) {
	        switch ( $option ) {
		        case 'schedule_match':
			        $title  = __( '(Re)schedule match', 'racketmanager' );
			        $button = __( 'Save', 'racketmanager' );
			        $action = 'setMatchDate';
			        break;
		        case 'adjust_team_score':
			        $title  = __( 'Adjust team score', 'racketmanager' );
			        $button = __( 'Change Results', 'racketmanager' );
			        $action = 'adjustTeamScore';
			        break;
		        case 'switch_home':
			        $title  = __( 'Switch home and away', 'racketmanager' );
			        $button = __( 'Switch', 'racketmanager' );
			        $action = 'switchHomeAway';
			        break;
		        case 'reset_match_result':
			        $title  = __( 'Reset match result', 'racketmanager' );
			        $button = __( 'Save', 'racketmanager' );
			        $action = 'resetMatchResult';
			        break;
		        default:
			        $valid   = false;
			        $msg     = __( 'Invalid match option', 'racketmanager' );
			        $title   = __( 'Unknown option', 'racketmanager' );
			        $action  = null;
			        $status  = 403;
			        break;
	        }
			if ( $valid ) {
				$filename = ( ! empty( $template ) ) ? 'match-option-modal-' . $template : 'match-option-modal';
				return $this->load_template(
					$filename,
					array(
						'match'  => $match,
						'title'  => $title,
						'modal'  => $modal,
						'option' => $option,
						'action' => $action,
						'button' => $button,
					),
					'match'
				);
			}
        } else {
            $msg = __( 'Match not found',  'racketmanager' );
        }
	    return $this->return_error_modal( $msg );
    }
	/**
	 * Function to display match status modal
	 *
	 *  [match-status match_id=ID status=x modal=x template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return false|string content
	 */
	public function show_match_status_modal( array $atts ): false|string {
		$args      = shortcode_atts(
			array(
				'match_id' => 0,
				'status'   => null,
				'modal'    => null,
				'template' => '',
			),
			$atts
		);
		$match_id = $args['match_id'];
		$status   = $args['status'];
		$template = $args['template'];
		$modal    = $args['modal'];
		$match    = get_match( $match_id );
		if ( $match ) {
			if ( empty( $status ) ) {
				if ( $match->is_walkover ) {
					if ( 'home' === $match->walkover ) {
						$status = 'walkover_player1';
					} else {
						$status = 'walkover_player2';
					}
				} elseif ( $match->is_retired ) {
					if ( 'home' === $match->retired ) {
						$status = 'retired_player1';
					} else {
						$status = 'retired_player2';
					}
				} elseif ( $match->is_shared ) {
					$status = 'share';
				} else {
					$status = null;
				}
			}
			$home_name      = $match->teams['home']->title;
			$away_name      = $match->teams['away']->title;
			$select         = array();
			$option         = new stdClass();
			$option->value  = 'walkover_player2';
			$option->select = 'walkover_player2';
			/* translators: %s: Home team name */
			$option->desc   = sprintf( $this->not_played_no_opponent, $home_name );
			$select[]       = $option;
			$option         = new stdClass();
			$option->value  = 'walkover_player1';
			$option->select = 'walkover_player1';
			/* translators: %s: Away team name */
			$option->desc = sprintf( $this->not_played_no_opponent, $away_name );
			$select[]     = $option;
			if ( $match->league->event->competition->is_player_entry ) {
				$option         = new stdClass();
				$option->value  = 'retired_player1';
				$option->select = 'retired_player1';
				/* translators: %s: Home team name */
				$option->desc   = sprintf( $this->retired_player, $home_name );
				$select[]       = $option;
				$option         = new stdClass();
				$option->value  = 'retired_player2';
				$option->select = 'retired_player2';
				/* translators: %s: Away team name */
				$option->desc = sprintf( $this->retired_player, $away_name );
				$select[]     = $option;
			}
			$option         = new stdClass();
			$option->value  = 'cancelled';
			$option->select = 'cancelled';
			$option->desc   = __( 'Cancelled', 'racketmanager' );
			$select[]       = $option;
			$option         = new stdClass();
			$option->value  = 'share';
			$option->select = 'share';
			$option->desc   = $this->not_played;
			$select[]       = $option;
			if ( $match->league->event->competition->is_team_entry ) {
				$option         = new stdClass();
				$option->value  = 'abandoned';
				$option->select = 'abandoned';
				$option->desc   = __( 'Abandoned', 'racketmanager' );
				$select[]       = $option;
			}
			$option         = new stdClass();
			$option->value  = 'none';
			$option->select = 'None';
			$option->desc   = __( 'Reset', 'racketmanager' );
			$select[]       = $option;
			$filename = ( ! empty( $template ) ) ? 'match-status-modal-' . $template : 'match-status-modal';

			return $this->load_template(
				$filename,
				array(
					'match'  => $match,
					'status' => $status,
					'modal'  => $modal,
					'select' => $select,
				),
				'match'
			);
		} else {
			$msg = __( 'Match not found',  'racketmanager' );
			return $this->return_error_modal( $msg );
		}
	}
    /**
     * Function to display match status modal
     *
     *  [rubber-status match_id=ID status=x modal=x template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return false|string content
     */
    public function show_rubber_status_modal( array $atts ): false|string {
        $args      = shortcode_atts(
            array(
                'rubber_id' => 0,
                'status'   => null,
                'modal'    => null,
                'template' => '',
            ),
            $atts
        );
        $rubber_id      = $args['rubber_id'];
        $status         = $args['status'];
        $template       = $args['template'];
        $modal          = $args['modal'];
        $rubber         = get_rubber( $rubber_id );
        if ( $rubber ) {
            $match          = get_match( $rubber->match_id );
            if ( $match ) {
                $home_name      = $match->teams['home']->title;
                $away_name      = $match->teams['away']->title;
                $select         = array();
                $option         = new stdClass();
                $option->value  = 'walkover_player2';
                $option->select = 'walkover_player2';
                /* translators: %s: Home team name */
                $option->desc   = sprintf( $this->not_played_no_opponent, $home_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'walkover_player1';
                $option->select = 'walkover_player1';
                /* translators: %s: Away team name */
                $option->desc   = sprintf( $this->not_played_no_opponent, $away_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'retired_player1';
                $option->select = 'retired_player1';
                /* translators: %s: Home team name */
                $option->desc   = sprintf( $this->retired_player, $home_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'retired_player2';
                $option->select = 'retired_player2';
                /* translators: %s: Away team name */
                $option->desc   = sprintf( $this->retired_player, $away_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'abandoned';
                $option->select = 'abandoned';
                $option->desc   = __( 'Abandoned', 'racketmanager' );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'share';
                $option->select = 'share';
                $option->desc   = $this->not_played;
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'none';
                $option->select = 'None';
                $option->desc   = __( 'Reset', 'racketmanager' );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'invalid_player1';
                $option->select = 'invalid_player1';
                /* translators: %s: Home team name */
                $option->desc   = sprintf( __( 'Invalid player - %s', 'racketmanager' ), $home_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'invalid_player2';
                $option->select = 'invalid_player2';
                /* translators: %s: Away team name */
                $option->desc   = sprintf( __( 'Invalid player - %s', 'racketmanager' ), $away_name );
                $select[]       = $option;
                $option         = new stdClass();
                $option->value  = 'invalid_players';
                $option->select = 'invalid_players';
                $option->desc   = __( 'Invalid player on both teams', 'racketmanager' );
                $select[]       = $option;
                $filename = ( ! empty( $template ) ) ? 'rubber-status-modal-' . $template : 'rubber-status-modal';

                return $this->load_template(
                    $filename,
                    array(
                        'match'  => $match,
                        'status' => $status,
                        'modal'  => $modal,
                        'select' => $select,
                    ),
                    'match'
                );
            } else {
                $msg = __( 'Match not found',  'racketmanager' );
                return $this->return_error_modal( $msg );
            }
        } else {
            $msg = __( 'Rubber not found',  'racketmanager' );
            return $this->return_error_modal( $msg );
        }
    }
}
