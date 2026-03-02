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

use function Racketmanager\get_event;
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
        $result = $this->result_league_not_found_if_missing( $league );

        if ( null === $result ) {
            $post   = $dto->post;
            $action = isset( $post['action'] ) ? strval( $post['action'] ) : null;

            $result = match ( $action ) {
                'delete'   => $this->delete_teams_from_league( $league, $post ),
                'withdraw' => $this->withdraw_teams_from_league( $league, $post ),
                default    => new Action_Result_DTO(),
            };
        }

        return $result;
    }

    public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
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
        $result = $this->result_league_not_found_if_missing( $league );

        if ( null === $result ) {
            $post     = $dto->post;
            $team_ids = isset( $post['table_id'] ) ? array_values( (array) $post['table_id'] ) : array();
            if ( empty( $team_ids ) ) {
                $result = $this->result_no_updates();
            } else {
                $sorted_team_ids = $this->sort_team_ids_for_ranking_mode( $league, $mode, $post, $team_ids );
                if ( null === $sorted_team_ids ) {
                    $result = new Action_Result_DTO( __( 'Invalid ranking mode', 'racketmanager' ), Admin_Message_Type::ERROR );
                } else {
                    $team_ranks = $this->build_league_team_rank_list( $sorted_team_ids );
                    $team_ranks = $league->get_ranking( $team_ranks );
                    $league->update_ranking( $team_ranks );

                    $result = new Action_Result_DTO( $this->msg_team_ranking_saved(), Admin_Message_Type::SUCCESS );
                }
            }
        }

        return $result;
    }

    public function manage_matches_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
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
            return $this->result_league_not_found();
        }

        $updates = $this->start_final_rounds( $league );
        if ( $updates ) {
            return new Action_Result_DTO( __( 'First round started', 'racketmanager' ), Admin_Message_Type::SUCCESS );
        }

        return new Action_Result_DTO( __( 'First round not started', 'racketmanager' ), Admin_Message_Type::ERROR, 'preliminary' );
    }

    public function set_championship_matches( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
        }

        $post         = $dto->post;
        $season       = intval( $dto->season );
        $input_rounds = $post['rounds'] ?? array();
        $action       = sanitize_text_field( wp_unslash( strval( $post['action'] ?? '' ) ) );

        $event_season    = $league->event->get_season_by_name( $season );
        $num_first_round = $league->championship->num_teams_first_round;
        $build = $this->build_championship_rounds_and_matches( $league, $season, $input_rounds, $num_first_round );
        if ( ! $build['valid'] ) {
            return new Action_Result_DTO( implode( '<br>', $build['messages'] ), Admin_Message_Type::ERROR );
        }

        $this->apply_championship_rounds_and_matches( $league, $season, $build['rounds'], $build['matches'], $event_season, $action );
        $message = ( 'replace' === $action ) ? __( 'Matches replaced', 'racketmanager' ) : __( 'Matches added', 'racketmanager' );

        return new Action_Result_DTO( $message, Admin_Message_Type::SUCCESS );
    }

    public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
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
            $messages ? implode( '<br>', $messages ) : $this->msg_no_updates(),
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
            $messages ? implode( '<br>', $messages ) : $this->msg_no_updates(),
            $messages ? Admin_Message_Type::SUCCESS : Admin_Message_Type::WARNING
        );
    }

    private function add_matches_to_league( object $league, array $post ): Action_Result_DTO {
        if ( ! isset( $post['match'] ) ) {
            return $this->result_no_updates();
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
            $match = $this->build_new_match_from_post_row( $league, $post, $i, $season, $final );
            if ( null === $match ) {
                --$num_matches;
                continue;
            }

            $league->add_match( $match );
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function build_new_match_from_post_row( object $league, array $post, int $i, ?string $season, ?string $final ): ?stdClass {
        $home = $post['home_team'][ $i ] ?? null;
        $away = $post['away_team'][ $i ] ?? null;
        if ( ! isset( $home, $away ) || strval( $away ) === strval( $home ) ) {
            return null;
        }

        $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
        $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;
        $date_time     = $this->build_match_date_time_from_post( $post, $i, $begin_hour, $begin_minutes );
        if ( null === $date_time ) {
            return null;
        }

        $match            = new stdClass();
        $match->date      = $date_time;
        $match->match_day = $this->match_day_from_post( $post, $i, '' );

        $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
        $match->home_team   = sanitize_text_field( wp_unslash( strval( $home ) ) );
        $match->away_team   = sanitize_text_field( wp_unslash( strval( $away ) ) );
        $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
        $match->league_id   = isset( $post['league_id'] ) ? sanitize_text_field( wp_unslash( strval( $post['league_id'] ) ) ) : $league->id;
        $match->season      = $season;
        $match->final_round = $final;
        $match->num_rubbers = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;

        return $match;
    }

    private function edit_matches_in_league( object $league, array $post ): Action_Result_DTO {
        if ( ! isset( $post['match'] ) ) {
            return $this->result_no_updates();
        }

        $num_matches = count( (array) $post['match'] );

        foreach ( (array) $post['match'] as $i => $match_id ) {
            $match = get_match( $match_id );
            if ( ! $match ) {
                continue;
            }

            $this->apply_match_edit_from_post_row( $league, $match, $post, $i );

            $league->update_match( $match );
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function apply_match_edit_from_post_row( object $league, object $match, array $post, int $i ): void {
        $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
        $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;

        $date_time = $this->build_match_date_time_from_post( $post, $i, $begin_hour, $begin_minutes );
        if ( null !== $date_time ) {
            $match->date = $date_time;
        }

        $match->league_id = $league->id;
        $match->match_day = $this->match_day_from_post( $post, $i, null );

        $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
        $match->home_team   = isset( $post['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['home_team'][ $i ] ) ) ) : '';
        $match->away_team   = isset( $post['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['away_team'][ $i ] ) ) ) : '';
        $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
        $match->final_round = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;
    }

    private function build_match_date_time_from_post( array $post, int $i, int $begin_hour, int $begin_minutes ): ?string {
        if ( isset( $post['myDatePicker'][ $i ] ) ) {
            $date = sanitize_text_field( wp_unslash( strval( $post['myDatePicker'][ $i ] ) ) );
            return $date . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
        }

        $index = ( isset( $post['year'][ $i ], $post['month'][ $i ], $post['day'][ $i ] ) ) ? $i : 0;
        if ( ! isset( $post['year'][ $index ], $post['month'][ $index ], $post['day'][ $index ] ) ) {
            return null;
        }

        $year  = intval( $post['year'][ $index ] );
        $month = intval( $post['month'][ $index ] );
        $day   = intval( $post['day'][ $index ] );

        return $year . '-' . $month . '-' . $day . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
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

            $home_team = $this->resolve_final_team_slot( $league, $match, (string) $match->home_team, 'home' );
            $away_team = $this->resolve_final_team_slot( $league, $match, (string) $match->away_team, 'away' );
            if ( null !== $home_team && null !== $away_team ) {
                $league->championship->set_teams( $match, $home_team, $away_team );
                $updates = true;
            }
        }

        $matches_list = $this->maybe_expand_matches_list_with_linked_matches( $matches_list, $multiple_legs );
        if ( $matches_list ) {
            $league->championship->update_final_results( $matches_list, array(), array(), array(), 1, $league->event->current_season['name'] );
        }

        return $updates;
    }

    private function maybe_expand_matches_list_with_linked_matches( array $matches_list, bool $multiple_legs ): array {
        if ( ! $multiple_legs || ! $matches_list ) {
            return $matches_list;
        }

        foreach ( $matches_list as $match_id ) {
            $match = get_match( $match_id );
            if ( $match && $match->linked_match ) {
                $matches_list[] = $match->linked_match;
            }
        }
        return $matches_list;
    }

    private function result_league_not_found(): Action_Result_DTO {
        return new Action_Result_DTO( $this->msg_league_not_found(), Admin_Message_Type::ERROR );
    }

    private function result_league_not_found_if_missing( mixed $league ): ?Action_Result_DTO {
        if ( ! $league ) {
            return $this->result_league_not_found();
        }
        return null;
    }

    private function result_no_updates(): Action_Result_DTO {
        return new Action_Result_DTO( $this->msg_no_updates(), Admin_Message_Type::WARNING );
    }

    /**
     * @return array<int,object>
     */
    private function build_league_team_rank_list( array $team_ids ): array {
        $team_ranks = array();
        foreach ( $team_ids as $key => $team_id ) {
            $team = get_league_team( $team_id );
            if ( $team ) {
                $team_ranks[ $key ] = $team;
            }
        }
        return $team_ranks;
    }

    private function sort_team_ids_for_ranking_mode( object $league, string $mode, array $post, array $team_ids ): ?array {
        return match ( $mode ) {
            'random'  => $this->sort_team_ids_random( $team_ids ),
            'ratings' => $this->sort_team_ids_by_ratings( $league, $post, $team_ids ),
            'manual'  => $this->sort_team_ids_manual( $post, $team_ids ),
            default   => null,
        };
    }

    private function sort_team_ids_random( array $team_ids ): array {
        shuffle( $team_ids );
        return $team_ids;
    }

    private function sort_team_ids_by_ratings( object $league, array $post, array $team_ids ): array {
        if ( isset( $post['rating_points'] ) ) {
            $rating_points = array_values( (array) $post['rating_points'] );
            array_multisort( $rating_points, SORT_ASC, $team_ids, SORT_ASC );
        }
        if ( $league->is_championship && $league->championship->num_seeds ) {
            $teams_seeded   = array_slice( $team_ids, 0, $league->championship->num_seeds );
            $teams_unseeded = array_slice( $team_ids, $league->championship->num_seeds );
            shuffle( $teams_unseeded );
            return array_merge( $teams_seeded, $teams_unseeded );
        }
        return $team_ids;
    }

    private function sort_team_ids_manual( array $post, array $team_ids ): array {
        if ( ! isset( $post['js-active'] ) || '1' !== strval( $post['js-active'] ) ) {
            $ranks = isset( $post['rank'] ) ? array_values( (array) $post['rank'] ) : array();
            if ( $ranks ) {
                array_multisort( $ranks, SORT_ASC, $team_ids, SORT_ASC );
            }
        }
        return $team_ids;
    }

    private function match_day_from_post( array $post, int $i, mixed $default ): mixed {
        $result = $default;

        if ( isset( $post['match_day'] ) ) {
            if ( is_array( $post['match_day'] ) ) {
                $result = isset( $post['match_day'][ $i ] ) ? intval( $post['match_day'][ $i ] ) : $default;
            } elseif ( ! empty( $post['match_day'] ) ) {
                $result = intval( $post['match_day'] );
            }
        }

        return $result;
    }

    /**
     * @return array{valid:bool,messages:array<int,string>,rounds:array<string,object>,matches:array<string,array<int,object>>}
     */
    private function build_championship_rounds_and_matches( object $league, int $season, array $input_rounds, int $num_first_round ): array {
        $messages = array();
        $rounds   = array();
        $matches  = array();
        $valid    = true;

        $next_round_date = null;
        $team_array      = array();

        foreach ( $input_rounds as $round ) {
            $round_number = intval( $round['round'] ?? 0 );
            $round_key    = strval( $round['key'] ?? '' );
            $round_date   = strval( $round['match_date'] ?? '' );
            $num_matches  = intval( $round['num_matches'] ?? 0 );

            if ( '' === $round_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round_number );
                $valid      = false;
                continue;
            }
            if ( ! empty( $next_round_date ) && $round_date >= $next_round_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round_number );
                $valid      = false;
                continue;
            }

            $teams = $league->championship->get_final_teams( $round_key );
            $round_build = $this->build_matches_for_round(
                $league,
                $season,
                array(
                    'key'         => $round_key,
                    'round'       => $round_number,
                    'match_date'  => $round_date,
                    'num_matches' => $num_matches,
                ),
                $teams,
                $num_first_round,
                $team_array
            );

            $matches[ $round_date ] = $round_build['matches'];
            $rounds[ $round_key ]   = $this->build_round_meta( $round_key, $round_number, $round_date );
            $next_round_date        = $round_date;
        }

        return array(
            'valid'    => $valid,
            'messages' => $messages,
            'rounds'   => $rounds,
            'matches'  => $matches,
        );
    }

    private function build_round_meta( string $key, int $round_number, string $round_date ): stdClass {
        $round_obj       = new stdClass();
        $round_obj->name = $key;
        $round_obj->num  = $round_number;
        $round_obj->date = $round_date;
        return $round_obj;
    }

    private function msg_league_not_found(): string {
        return __( 'League not found', 'racketmanager' );
    }

    private function msg_no_updates(): string {
        return __( 'No updates', 'racketmanager' );
    }

    private function msg_team_ranking_saved(): string {
        return __( 'Team ranking saved', 'racketmanager' );
    }

    private function get_first_round_team_array( int $num_matches ): array {
        return match ( $num_matches ) {
            1       => array( 1 ),
            2       => array( 1, 3 ),
            4       => array( 1, 5, 3, 7 ),
            8       => array( 1, 9, 4, 12, 11, 14, 7, 15 ),
            16      => array( 1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31 ),
            32      => array( 1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63 ),
            default => array(),
        };
    }

    private function get_round_host_for_match( string $round_key, int $round_number ): ?string {
        if ( 'final' === $round_key ) {
            return null;
        }
        return ( $round_number & 1 ) ? 'home' : 'away';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function get_final_team_slot_names(
        bool $first_round,
        array $team_array,
        int $i,
        int $num_first_round,
        ?string $prev_round_name,
        int $home_team,
        int $away_team
    ): array {
        if ( $first_round ) {
            $home_seed = $team_array[ $i ] ?? 1;
            $away_seed = $num_first_round + 1 - $home_seed;
            return array( $home_seed . '_', $away_seed . '_' );
        }
        return array( '1_' . $prev_round_name . '_' . $home_team, '1_' . $prev_round_name . '_' . $away_team );
    }

    /**
     * @param array<string,object> $teams
     * @param array<int,int> $team_array
     * @return array{matches:array<int,object>}
     */
    private function build_matches_for_round(
        object $league,
        int $season,
        array $round,
        array $teams,
        int $num_first_round,
        array &$team_array
    ): array {
        $matches = array();

        $round_key    = strval( $round['key'] ?? '' );
        $round_number = intval( $round['round'] ?? 0 );
        $round_date   = strval( $round['match_date'] ?? '' );
        $num_matches  = intval( $round['num_matches'] ?? 0 );

        $first_round = ( 1 === $round_number );
        $prev_round_name = null;
        $home_team = 1;
        $away_team = 2;

        if ( $first_round ) {
            $team_array = $this->get_first_round_team_array( $num_matches );
        } else {
            $prev_round      = $round_number - 1;
            $prev_round_name = $league->championship->get_final_keys( $prev_round );
        }

        for ( $i = 0; $i < $num_matches; ++$i ) {
            $match            = new stdClass();
            $match->date      = $round_date . ' 00:00:00';
            $match->match_day = null;

            $host = $this->get_round_host_for_match( $round_key, $round_number );
            if ( null !== $host ) { $match->host = $host; }

            [ $home_team_name, $away_team_name ] = $this->get_final_team_slot_names( $first_round, $team_array, $i, $num_first_round, $prev_round_name, $home_team, $away_team );

            if ( isset( $teams[ $home_team_name ] ) ) {
                $match->home_team = $teams[ $home_team_name ]->id;
            }
            if ( isset( $teams[ $away_team_name ] ) ) {
                $match->away_team = $teams[ $away_team_name ]->id;
            }

            if ( $first_round ) {
                ++$home_team;
                $away_team = $num_first_round + 1 - $home_team;
            } else {
                $home_team += 2;
                $away_team += 2;
            }

            $match->location    = null;
            $match->league_id   = $league->id;
            $match->season      = $season;
            $match->final_round = $round_key;
            $match->num_rubbers = $league->num_rubbers;

            $matches[] = $match;
        }

        return array( 'matches' => $matches );
    }

    private function apply_championship_rounds_and_matches(
        object $league,
        int $season,
        array $rounds,
        array $matches,
        array $event_season,
        string $action
    ): void {
        $league->set_rounds( $season, $rounds );
        if ( 'replace' === $action ) {
            $league->delete_season_matches( $season );
        }

        $event_season['match_dates'] = array();
        foreach ( array_reverse( $matches ) as $match_date => $round_matches ) {
            $event_season['match_dates'][] = $match_date;
            foreach ( $round_matches as $match ) {
                $league->add_match( $match );
            }
        }

        if ( $league->championship->is_consolation ) {
            return;
        }

        $event_season['num_match_days'] = count( $event_season['match_dates'] );
        $event = get_event( $league->event_id );
        if ( ! $event ) {
            return;
        }
        $event_seasons            = $event->get_seasons();
        $event_seasons[ $season ] = $event_season;
        $event->update_seasons( $event_seasons );
    }

    private function resolve_final_team_slot( object $league, object $match, string $team_id, string $side ): ?int {
        $resolved_team_id = null;

        if ( '-1' === $team_id ) {
            $resolved_team_id = -1;
        } elseif ( str_contains( $team_id, '_' ) ) {
            $parts = explode( '_', $team_id );
            $rank  = $parts[0] ?? '';
            $group = $parts[1] ?? '';

            $teams = $league->get_league_teams(
                array(
                    'rank'             => $rank,
                    'group'            => $group,
                    'reset_query_args' => true,
                )
            );

            if ( ! $teams ) {
                $resolved_team_id = -1;
            } else {
                $resolved_team_id = $teams[0]->id;
            }
        }

        if ( null !== $resolved_team_id && -1 !== $resolved_team_id ) {
            if ( 'home' === $side ) {
                $match->home_team     = $resolved_team_id;
                $match->teams['home'] = $league->get_team_dtls( $resolved_team_id );
            } else {
                $match->away_team     = $resolved_team_id;
                $match->teams['away'] = $league->get_team_dtls( $resolved_team_id );
            }
        }

        return $resolved_team_id;
    }
}
