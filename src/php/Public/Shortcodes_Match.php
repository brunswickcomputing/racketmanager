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
