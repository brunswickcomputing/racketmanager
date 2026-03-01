<?php
/**
 * Championship Admin Service (generic)
 *
 * Pure application layer: no capability checks, no nonce checks, no output.
 *
 * @package RacketManager
 * @subpackage Services/Admin
 */

namespace Racketmanager\Services\Admin;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Services\League_Service;
use stdClass;

use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

readonly final class Championship_Admin_Service {

    public function __construct(
        private League_Service $league_service,
    ) {
    }

    public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $post = $dto->post;
        $action = isset( $post['action'] ) ? strval( $post['action'] ) : null;

        if ( 'delete' === $action ) {
            return $this->delete_teams_from_league( $league, $post );
        }
        if ( 'withdraw' === $action ) {
            return $this->withdraw_teams_from_league( $league, $post );
        }

        return new Action_Result_DTO();
    }

    public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $post   = $dto->post;
        $season = isset( $post['season'] ) ? intval( $post['season'] ) : null;

        if ( empty( $post['team'] ) || ! is_array( $post['team'] ) ) {
            return new Action_Result_DTO( __( 'No teams selected', 'racketmanager' ), Admin_Message_Type::WARNING );
        }

        $messages = array();
        $any_error = false;

        foreach ( $post['team'] as $team_id ) {
            $team_id = intval( $team_id );
            try {
                $this->league_service->add_team_to_league( $team_id, $league->get_id(), $season );
                $messages[] = __( 'Team added', 'racketmanager' );
            } catch ( Team_Not_Found_Exception|League_Not_Found_Exception $e ) {
                $messages[] = $e->getMessage();
                $any_error  = true;
            }
        }

        return new Action_Result_DTO(
            implode( '<br>', $messages ),
            $any_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS
        );
    }

    public function rank_teams( Draw_Action_Request_DTO $dto, string $mode ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $post = $dto->post;
        $team_ids = isset( $post['table_id'] ) ? array_values( (array) $post['table_id'] ) : array();
        if ( empty( $team_ids ) ) {
            return new Action_Result_DTO( __( 'No updates', 'racketmanager' ), Admin_Message_Type::WARNING );
        }

        switch ( $mode ) {
            case 'random':
                shuffle( $team_ids );
                break;
            case 'ratings':
                if ( isset( $post['rating_points'] ) ) {
                    $rating_points = array_values( (array) $post['rating_points'] );
                    array_multisort( $rating_points, SORT_ASC, $team_ids, SORT_ASC );
                }
                if ( $league->is_championship && $league->championship->num_seeds ) {
                    $teams_seeded   = array_slice( $team_ids, 0, $league->championship->num_seeds );
                    $teams_unseeded = array_slice( $team_ids, $league->championship->num_seeds );
                    shuffle( $teams_unseeded );
                    $team_ids = array_merge( $teams_seeded, $teams_unseeded );
                }
                break;
            case 'manual':
                if ( ! isset( $post['js-active'] ) || '1' !== strval( $post['js-active'] ) ) {
                    $ranks = isset( $post['rank'] ) ? array_values( (array) $post['rank'] ) : array();
                    if ( $ranks ) {
                        array_multisort( $ranks, SORT_ASC, $team_ids, SORT_ASC );
                    }
                }
                break;
            default:
                return new Action_Result_DTO( __( 'Invalid ranking mode', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $team_ranks = array();
        foreach ( $team_ids as $key => $team_id ) {
            $team = get_league_team( $team_id );
            if ( $team ) {
                $team_ranks[ $key ] = $team;
            }
        }

        $team_ranks = $league->get_ranking( $team_ranks );
        $league->update_ranking( $team_ranks );

        return new Action_Result_DTO( __( 'Team ranking saved', 'racketmanager' ), Admin_Message_Type::SUCCESS );
    }

    public function manage_matches_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $post = $dto->post;
        $mode = ! empty( $post['mode'] ) ? sanitize_text_field( wp_unslash( strval( $post['mode'] ) ) ) : null;

        if ( 'add' === $mode ) {
            return $this->add_matches_to_league( $league, $post );
        }

        return $this->edit_matches_in_league( $league, $post );
    }

    public function start_finals( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $updates = $this->start_final_rounds( $league );
        if ( $updates ) {
            return new Action_Result_DTO( __( 'First round started', 'racketmanager' ), Admin_Message_Type::SUCCESS );
        }

        return new Action_Result_DTO( __( 'First round not started', 'racketmanager' ), Admin_Message_Type::ERROR, 'preliminary' );
    }

    public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return new Action_Result_DTO( __( 'League not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $post = $dto->post;

        $custom      = $post['custom'] ?? array();
        $matches     = $post['matches'] ?? array();
        $home_points = $post['home_points'] ?? array();
        $away_points = $post['away_points'] ?? array();
        $round       = isset( $post['round'] ) ? intval( $post['round'] ) : null;
        $season      = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;

        $league->championship->update_final_results( $matches, $home_points, $away_points, $custom, $round, $season );

        return new Action_Result_DTO( __( 'Final results updated', 'racketmanager' ), Admin_Message_Type::SUCCESS );
    }

    // -------- internal ports (no validation, minimal sanitization) --------

    private function delete_teams_from_league( object $league, array $post ): Action_Result_DTO {
        $season   = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $messages = array();

        if ( isset( $post['team'] ) && is_array( $post['team'] ) ) {
            foreach ( $post['team'] as $team_id ) {
                $league->delete_team( intval( $team_id ), $season );
                $messages[] = intval( $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
            }
        }

        return new Action_Result_DTO(
            $messages ? implode( '<br>', $messages ) : __( 'No updates', 'racketmanager' ),
            $messages ? Admin_Message_Type::SUCCESS : Admin_Message_Type::WARNING
        );
    }

    private function withdraw_teams_from_league( object $league, array $post ): Action_Result_DTO {
        $season   = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $messages = array();

        if ( isset( $post['team'] ) && is_array( $post['team'] ) ) {
            foreach ( $post['team'] as $team_id ) {
                $team = get_team( $team_id );
                $league->withdraw_team( intval( $team_id ), $season );
                $title = $team ? $team->title : strval( $team_id );
                $messages[] = $title . ' ' . __( 'withdrawn', 'racketmanager' );
            }
        }

        return new Action_Result_DTO(
            $messages ? implode( '<br>', $messages ) : __( 'No updates', 'racketmanager' ),
            $messages ? Admin_Message_Type::SUCCESS : Admin_Message_Type::WARNING
        );
    }

    private function add_matches_to_league( object $league, array $post, ?string $group = null ): Action_Result_DTO {
        if ( ! isset( $post['match'] ) ) {
            return new Action_Result_DTO( __( 'No updates', 'racketmanager' ), Admin_Message_Type::WARNING );
        }

        $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $final  = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;

        if ( $final ) {
            $final_exists = $league->get_matches(
                array(
                    'final'  => $final,
                    'season' => $season,
                )
            );
            if ( $final_exists ) {
                return new Action_Result_DTO( sprintf( __( 'Matches already exist for %s', 'racketmanager' ), $final ), Admin_Message_Type::ERROR );
            }
        }

        $num_matches = count( (array) $post['match'] );

        foreach ( (array) $post['match'] as $i => $match_id ) {
            $match = new stdClass();

            $home = $post['home_team'][ $i ] ?? null;
            $away = $post['away_team'][ $i ] ?? null;
            if ( ! isset( $home, $away ) || strval( $away ) === strval( $home ) ) {
                --$num_matches;
                continue;
            }

            $index = isset( $post['myDatePicker'][ $i ] ) ? $i : 0;
            $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
            $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;

            if ( isset( $post['myDatePicker'][ $index ] ) ) {
                $date = sanitize_text_field( wp_unslash( strval( $post['myDatePicker'][ $index ] ) ) );
                $match->date = $date . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
                $match->match_day = '';
                if ( isset( $post['match_day'][ $i ] ) ) {
                    $match->match_day = sanitize_text_field( wp_unslash( strval( $post['match_day'][ $i ] ) ) );
                } elseif ( ! empty( $post['match_day'] ) ) {
                    $match->match_day = intval( $post['match_day'] );
                }
                $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
                $match->home_team   = sanitize_text_field( wp_unslash( strval( $home ) ) );
                $match->away_team   = sanitize_text_field( wp_unslash( strval( $away ) ) );
                $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
                $match->league_id   = isset( $post['league_id'] ) ? sanitize_text_field( wp_unslash( strval( $post['league_id'] ) ) ) : null;
                $match->season      = $season;
                $match->group       = $group;
                $match->final_round = $final;
                $match->num_rubbers = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;
                $league->add_match( $match );
            }
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function edit_matches_in_league( object $league, array $post ): Action_Result_DTO {
        if ( ! isset( $post['match'] ) ) {
            return new Action_Result_DTO( __( 'No updates', 'racketmanager' ), Admin_Message_Type::WARNING );
        }

        $num_matches = count( (array) $post['match'] );

        foreach ( (array) $post['match'] as $i => $match_id ) {
            $match = get_match( $match_id );
            if ( ! $match ) {
                continue;
            }

            $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
            $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;

            if ( isset( $post['myDatePicker'][ $i ] ) ) {
                $date = sanitize_text_field( wp_unslash( strval( $post['myDatePicker'][ $i ] ) ) );
                $date = $date . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
            } else {
                $index = ( isset( $post['year'][ $i ], $post['month'][ $i ], $post['day'][ $i ] ) ) ? $i : 0;
                $year  = isset( $post['year'][ $index ] ) ? intval( $post['year'][ $index ] ) : 0;
                $month = isset( $post['month'][ $index ] ) ? intval( $post['month'][ $index ] ) : 0;
                $day   = isset( $post['day'][ $index ] ) ? intval( $post['day'][ $index ] ) : 0;
                $date  = $year . '-' . $month . '-' . $day . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
            }

            $match->date      = $date;
            $match->league_id = $league->id;
            $match->match_day = null;

            if ( isset( $post['match_day'] ) ) {
                if ( is_array( $post['match_day'] ) ) {
                    $match->match_day = isset( $post['match_day'][ $i ] ) ? intval( $post['match_day'][ $i ] ) : null;
                } elseif ( ! empty( $post['match_day'] ) ) {
                    $match->match_day = intval( $post['match_day'] );
                }
            }

            $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
            $match->home_team   = isset( $post['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['home_team'][ $i ] ) ) ) : '';
            $match->away_team   = isset( $post['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['away_team'][ $i ] ) ) ) : '';
            $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
            $match->final_round = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;

            $league->update_match( $match );
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function start_final_rounds( object $league ): bool {
        $updates       = false;
        $multiple_legs = false;
        $round_name    = $league->championship->get_final_keys( 1 );

        $match_args = array(
            'final'            => $round_name,
            'limit'            => false,
            'match_day'        => -1,
            'reset_query_args' => true,
        );

        if ( $league->event->current_season['home_away'] ) {
            $multiple_legs     = true;
            $match_args['leg'] = 1;
        }

        $matches_list = array();
        $matches      = $league->get_matches( $match_args );

        foreach ( $matches as $match ) {
            $matches_list[] = $match->id;

            $home_team_id = $match->home_team;
