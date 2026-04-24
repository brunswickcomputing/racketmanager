<?php
/**
 * Shortcodes_Match API: Shortcodes_Match class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager\Public;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use stdClass;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_rubber;

/**
 * Class to implement the Shortcodes_Match object
 */
class Shortcodes_Match extends Shortcodes {
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
        $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
        if ( $dto ) {
            $match = $dto->fixture;
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
                case 'reset_fixture_result':
                    $title  = __( 'Reset match result', 'racketmanager' );
                    $button = __( 'Save', 'racketmanager' );
                    $action = 'resetMatchResult';
                    break;
                default:
                    $valid   = false;
                    $msg     = __( 'Invalid match option', 'racketmanager' );
                    $title   = __( 'Unknown option', 'racketmanager' );
                    $button  = null;
                    $action  = null;
                    break;
            }
            if ( $valid ) {
                $filename = ( ! empty( $template ) ) ? 'match-option-modal-' . $template : 'match-option-modal';
                return $this->load_template(
                    $filename,
                    array(
                        'dto'    => $dto,
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
            $msg = $this->match_not_found;
        }
        return $this->return_error( $msg, 'modal' );
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
        $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
        if ( $dto ) {
            $match = $dto->fixture;
            if ( empty( $status ) ) {
                if ( $match->is_walkover() ) {
                    if ( 'home' === $match->get_walkover() ) {
                        $status = 'walkover_player1';
                    } else {
                        $status = 'walkover_player2';
                    }
                } elseif ( $match->is_retired() ) {
                    if ( 'home' === $match->get_retired() ) {
                        $status = 'retired_player1';
                    } else {
                        $status = 'retired_player2';
                    }
                } elseif ( $match->is_shared() ) {
                    $status = 'share';
                } else {
                    $status = null;
                }
            }
            $home_name      = $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' );
            $away_name      = $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' );
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
            if ( $dto->competition->is_player_entry ) {
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
            if ( $dto->competition->is_team_entry ) {
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
                    'dto'    => $dto,
                    'match'  => $match,
                    'status' => $status,
                    'modal'  => $modal,
                    'select' => $select,
                ),
                'match'
            );
        } else {
            $msg = $this->match_not_found;
            return $this->return_error( $msg, 'modal' );
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
                'id'       => 0,
                'status'   => null,
                'modal'    => null,
                'template' => '',
            ),
            $atts
        );
        $rubber_id = $args['id'];
        $status    = $args['status'];
        $template  = $args['template'];
        $modal     = $args['modal'];
        $rubber    = get_rubber( $rubber_id );
        if ( $rubber ) {
            $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $rubber->match_id );
            if ( $dto ) {
                $match          = $dto->fixture;
                $home_name      = $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' );
                $away_name      = $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' );
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
                        'dto'    => $dto,
                        'match'  => $match,
                        'status' => $status,
                        'modal'  => $modal,
                        'select' => $select,
                        'rubber' => $rubber,
                    ),
                    'match'
                );
            } else {
                $msg = $this->match_not_found;
            }
        } else {
            $msg = __( 'Rubber not found',  'racketmanager' );
        }
        return $this->return_error( $msg, 'modal' );
    }
    /**
     * Function to display match status modal
     *
     *  [match-card match_id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return false|string content
     */
    public function show_match_card( array $atts ): false|string {
        $args      = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
            ),
            $atts
        );
        $match_id = $args['id'];
        $template = $args['template'];
        if ( $match_id ) {
            $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
            if ( $dto ) {
                $match = $dto->fixture;
                if ( ! empty( $dto->league->num_rubbers ) ) {
                    $match->rubbers = $match->get_rubbers();
                    $template       = 'rubbers';
                }
                $sponsor_html                  = '';
                $template_args['dto']          = $dto;
                $template_args['match']        = $match;
                $template_args['sponsor_html'] = $sponsor_html;
                $filename                      = ( ! empty( $template ) ) ? 'match-card-' . $template : 'match-card';
                return $this->load_template(
                    $filename,
                    $template_args,
                    'match'
                );
            } else {
                $msg = $this->no_match_id;
            }
        } else {
            $msg =  $this->match_not_found;
        }
        return $this->return_error( $msg );
    }
    /**
     * Function to display match status modal
     *
     *  [show-score match_id=ID team=x opponent=x home_away=x template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return false|string content
     */
    public function show_score( array $atts ): false|string {
        $args        = shortcode_atts(
            array(
                'id'        => 0,
                'team'      => null,
                'opponent'  => null,
                'home_away' => false,
                'template'  => '',
            ),
            $atts
        );
        $match_id    = $args['id'];
        $team_id     = $args['team'];
        $opponent_id = $args['opponent'];
        $home_away   = $args['home_away'];
        $template    = $args['template'];
        $msg         = null;
        if ( $match_id ) {
            $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
            if ( $dto ) {
                $match = $dto->fixture;
                $score_team_1 = null;
                $score_team_2 = null;
                // unplayed match.
                if ( null === $match->get_home_points() && null === $match->get_away_points() ) {
                    $date      = str_starts_with( $match->get_date(), '0000-00-00' ) ? 'N/A' : mysql2date( 'D d/m/Y', $match->get_date() );
                    $match_day = $match->get_match_day() ? __( 'Match Day', 'racketmanager' ) . ' ' . $match->get_match_day() : '';
                    if ( $home_away ) {
                        $output = "<span class='unplayedMatch'>" . $match_day . '<br/>' . $date . '</span><br/>';
                    } else {
                        $output = "<span class='unplayedMatch'>&nbsp;</span>";
                    }
                    return $output;
                    // match at home.
                } elseif ( strval( $team_id ) === $match->get_home_team() ) {
                    $score_team_1 = $match->get_home_points();
                    $score_team_2 = $match->get_away_points();
                    // match away.
                } elseif ( strval( $opponent_id ) === $match->get_home_team() ) {
                    $score_team_1 = $match->get_away_points();
                    $score_team_2 = $match->get_home_points();
                }
                if ( empty( $msg ) ) {
                    if ( strval( $team_id ) === (string) $match->get_winner_id() ) {
                        $score_class = 'winner';
                    } elseif ( strval( $team_id ) === (string) $match->get_loser_id() ) {
                        $score_class = 'loser';
                    } elseif ( -1 === $match->get_winner_id() ) {
                        $score_class = 'tie';
                    } else {
                        $score_class = null;
                    }
                    if ( $home_away ) {
                        $link_title = __( 'Match Day', 'racketmanager' ) . ' ' . $match->get_match_day();
                    } else {
                        $link_title = '';
                    }
                    $template_args['dto']          = $dto;
                    $template_args['match']        = $match;
                    $template_args['score_class']  = $score_class;
                    $template_args['link_title']   = $link_title;
                    $template_args['score_team_1'] = $score_team_1;
                    $template_args['score_team_2'] = $score_team_2;
                    $template_args['home_away']    = $home_away;
                    $filename                      = ! empty( $template ) ? 'score-' . $template : 'score';
                    return $this->load_template(
                        $filename,
                        $template_args,
                        'match'
                    );
                }
            } else {
                $msg = $this->match_not_found;
            }
        } else {
            $msg = $this->no_match_id;
        }
        return $this->return_error( $msg );
    }
    /**
     * Function to display match header
     *
     *  [match-header id=ID edit=x template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_match_header( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'edit'     => false,
                'template' => '',
            ),
            $atts
        );
        $match_id = $args['id'];
        $edit     = $args['edit'];
        $template = $args['template'];
        if ( $match_id ) {
            $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
            if ( $dto ) {
                $match                      = $dto->fixture;
                $template_args['dto']       = $dto;
                $template_args['match']     = $match;
                $template_args['edit_mode'] = $edit;
                $filename               = ! empty( $template ) ? 'match-header-' . $template : 'match-header';
                return $this->load_template(
                    $filename,
                    $template_args,
                    'match'
                );
            } else {
                $msg = $this->match_not_found;
            }
        } else {
            $msg = $this->no_match_id;
        }
        return $this->return_error( $msg );
    }
    /**
     * Function to display match detail
     *
     *  [match-detail id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_match_detail( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'player'   => null,
                'template' => '',
            ),
            $atts
        );
        $match_id  = $args['id'];
        $player_id = $args['player'];
        $template  = $args['template'];
        if ( $match_id ) {
            $dto = $this->fixture_detail_service->get_fixture_with_details( (int) $match_id );
            if ( $dto ) {
                $match = $dto->fixture;
                if ( null === $match->get_final() ) {
                    $match->round = '';
                    $match->type  = 'league';
                } else {
                    $match->round = $match->get_final();
                    $match->type  = 'tournament';
                }
                $match_args = null;
                if ( $player_id ) {
                    $player = get_player( $player_id );
                    if ( $player ) {
                        $template_args['match_player'] = $player;
                        $match_args                    = $player->id;
                    }
                }
                $match->rubbers = $match->get_rubbers( $match_args );
                $is_update_allowed                  = $dto->is_update_allowed;
                $template_args['dto']               = $dto;
                $template_args['match']             = $match;
                $template_args['is_update_allowed'] = $is_update_allowed;
                if ( ! empty( $dto->league->num_rubbers ) ) {
                    $template = 'teams-scores';
                }
                $filename = ! empty( $template ) ? 'detail-' . $template : 'detail';
                return $this->load_template(
                    $filename,
                    $template_args,
                    'match'
                );
            } else {
                $msg = $this->match_not_found;
            }
        } else {
            $msg = $this->no_match_id;
        }
        return $this->return_error( $msg );
    }
}
