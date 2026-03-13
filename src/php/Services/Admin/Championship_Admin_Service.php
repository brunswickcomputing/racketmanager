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
use Racketmanager\Domain\DTO\Admin\Overview\Tournament_Overview_Action_Request_DTO;
use Racketmanager\Domain\Fixture;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Overview\Tournament_Overview_Action_Handler_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;

use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

readonly final class Championship_Admin_Service implements Draw_Action_Handler_Interface, Tournament_Overview_Action_Handler_Interface {

    public function __construct(
        private League_Service $league_service,
        private Fixture_Service $fixture_service,
        private Tournament_Service $tournament_service,
    ) {
    }

    public function contact_teams( Tournament_Overview_Action_Request_DTO $dto ): Action_Result_DTO {
        $post          = $dto->post;
        $tournament_id = $dto->tournament_id;
        $message       = isset( $post['emailMessage'] ) ? htmlspecialchars_decode( strval( $post['emailMessage'] ) ) : null;
        $active        = isset( $post['contactTeamActive'] );

        try {
            $sent = $this->tournament_service->contact_teams( $tournament_id, $message, $active );
            if ( $sent ) {
                return new Action_Result_DTO(
                    message: __( 'Email sent to players', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS
                );
            }

            return new Action_Result_DTO(
                message: __( 'Unable to send email', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR
            );
        }
    }

    public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        $result = $this->result_league_not_found_if_missing( $league );

        if ( null === $result ) {
            $post   = $dto->post;
            $action = isset( $post['action'] ) ? strval( $post['action'] ) : null;

            $result = match ( $action ) {
                'delete' => $this->delete_teams_from_league( $league, $post ),
                'withdraw' => $this->withdraw_teams_from_league( $league, $post ),
                default => new Action_Result_DTO(),
            };
        }

        return $result;
    }

    private function result_league_not_found_if_missing( mixed $league ): ?Action_Result_DTO {
        if ( ! $league ) {
            return $this->result_league_not_found();
        }

        return null;
    }

    private function result_league_not_found(): Action_Result_DTO {
        return new Action_Result_DTO( $this->msg_league_not_found(), Admin_Message_Type::ERROR );
    }

    private function msg_league_not_found(): string {
        return __( 'League not found', 'racketmanager' );
    }

    private function delete_teams_from_league( object $league, array $post ): Action_Result_DTO {
        $season    = isset( $post['season'] ) ? intval( sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) ) : 0;
        $messages  = array();
        $any_error = false;

        if ( isset( $post['team'] ) && is_array( $post['team'] ) ) {
            foreach ( $post['team'] as $team_id ) {
                try {
                    $this->league_service->remove_team_from_league( intval( $team_id ), $league->get_id(), $season );
                    $messages[] = intval( $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
                } catch ( Team_Has_Matches_Exception $e ) {
                    $messages[] = intval( $team_id ) . ': ' . $e->getMessage();
                    $any_error  = true;
                }
            }
        }

        $message_type = Admin_Message_Type::WARNING;
        if ( $any_error ) {
            $message_type = Admin_Message_Type::ERROR;
        } elseif ( $messages ) {
            $message_type = Admin_Message_Type::SUCCESS;
        }

        return new Action_Result_DTO(
            $messages ? implode( '<br>', $messages ) : $this->msg_no_updates(),
            $message_type
        );
    }

    private function msg_no_updates(): string {
        return __( 'No updates', 'racketmanager' );
    }

    private function withdraw_teams_from_league( object $league, array $post ): Action_Result_DTO {
        $season   = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $messages = array();

        if ( isset( $post['team'] ) && is_array( $post['team'] ) ) {
            foreach ( $post['team'] as $team_id ) {
                $team = get_team( $team_id );
                $league->withdraw_team( intval( $team_id ), $season );
                $title      = $team ? $team->title : strval( $team_id );
                $messages[] = $title . ' ' . __( 'withdrawn', 'racketmanager' );
            }
        }

        return new Action_Result_DTO(
            $messages ? implode( '<br>', $messages ) : $this->msg_no_updates(),
            $messages ? Admin_Message_Type::SUCCESS : Admin_Message_Type::WARNING
        );
    }

    // -------- internal ports (no validation, minimal sanitization) --------

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

        $messages  = array();
        $any_error = false;

        foreach ( $post['team'] as $team_id ) {
            $team_id = sanitize_text_field( wp_unslash( $team_id ) );
            if ( ! is_numeric( $team_id ) ) {
                //TODO: handle team in format 2_round_match number
            }
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

    private function result_no_updates(): Action_Result_DTO {
        return new Action_Result_DTO( $this->msg_no_updates(), Admin_Message_Type::WARNING );
    }

    private function sort_team_ids_for_ranking_mode( object $league, string $mode, array $post, array $team_ids ): ?array {
        return match ( $mode ) {
            'random' => $this->sort_team_ids_random( $team_ids ),
            'ratings' => $this->sort_team_ids_by_ratings( $league, $post, $team_ids ),
            'manual' => $this->sort_team_ids_manual( $post, $team_ids ),
            default => null,
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

    private function msg_team_ranking_saved(): string {
        return __( 'Team ranking saved', 'racketmanager' );
    }

    public function manage_fixtures_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
        }

        $post = $dto->post;
        $mode = ! empty( $post['mode'] ) ? sanitize_text_field( wp_unslash( strval( $post['mode'] ) ) ) : null;

        if ( 'add' === $mode ) {
            return $this->add_fixtures_to_league( $league, $post );
        }

        return $this->edit_fixtures_in_league( $league, $post );
    }

    private function add_fixtures_to_league( object $league, array $post ): Action_Result_DTO {
        if ( ! isset( $post['fixture'] ) ) {
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
                return new Action_Result_DTO( sprintf( __( 'Fixtures already exist for %s', 'racketmanager' ), $final ), Admin_Message_Type::ERROR );
            }
        }

        $num_fixtures = count( (array) $post['fixture'] );

        foreach ( (array) $post['fixture'] as $i => $fixture_id ) {
            $fixture = $this->build_new_fixture_from_post_row( $league, $post, $i, $season, $final );
            if ( null === $fixture ) {
                -- $num_fixtures;
                continue;
            }

            $this->fixture_service->create_fixture( new Fixture( $fixture ), $league );
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Fixture added', '%d Fixtures added', $num_fixtures, 'racketmanager' ), $num_fixtures ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function build_new_fixture_from_post_row( object $league, array $post, int $i, ?string $season, ?string $final ): ?stdClass {
        $home = $post['home_team'][ $i ] ?? null;
        $away = $post['away_team'][ $i ] ?? null;
        if ( ! isset( $home, $away ) || strval( $away ) === strval( $home ) ) {
            return null;
        }

        $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
        $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;
        $date_time     = $this->build_fixture_date_time_from_post( $post, $i, $begin_hour, $begin_minutes );
        if ( null === $date_time ) {
            return null;
        }

        $fixture            = new stdClass();
        $fixture->date      = $date_time;
        $fixture->match_day = $this->match_day_from_post( $post, $i, '' );

        $fixture->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
        $fixture->home_team   = sanitize_text_field( wp_unslash( strval( $home ) ) );
        $fixture->away_team   = sanitize_text_field( wp_unslash( strval( $away ) ) );
        $fixture->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
        $fixture->league_id   = isset( $post['league_id'] ) ? sanitize_text_field( wp_unslash( strval( $post['league_id'] ) ) ) : $league->id;
        $fixture->season      = $season;
        $fixture->final       = $final;
        $fixture->num_rubbers = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;

        return $fixture;
    }

    private function build_fixture_date_time_from_post( array $post, int $i, int $begin_hour, int $begin_minutes ): ?string {
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

    private function edit_fixtures_in_league( object $league, array $post ): Action_Result_DTO {
        if ( ! isset( $post['fixture'] ) ) {
            return $this->result_no_updates();
        }

        $num_fixtures = count( (array) $post['fixture'] );

        foreach ( (array) $post['fixture'] as $i => $fixture_id ) {
            $fixture = get_match( $fixture_id );
            if ( ! $fixture ) {
                continue;
            }

            $this->apply_fixture_edit_from_post_row( $league, $fixture, $post, $i );

            $this->fixture_service->update_fixture( new Fixture( $fixture ) );
        }

        return new Action_Result_DTO(
            sprintf( _n( '%d Fixture updated', '%d Fixtures updated', $num_fixtures, 'racketmanager' ), $num_fixtures ),
            Admin_Message_Type::SUCCESS
        );
    }

    private function apply_fixture_edit_from_post_row( object $league, object $fixture, array $post, int $i ): void {
        $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
        $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;

        $date_time = $this->build_fixture_date_time_from_post( $post, $i, $begin_hour, $begin_minutes );
        if ( null !== $date_time ) {
            $fixture->date = $date_time;
        }

        $fixture->league_id = $league->id;
        $fixture->match_day = $this->match_day_from_post( $post, $i, null );

        $fixture->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
        $fixture->home_team   = isset( $post['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['home_team'][ $i ] ) ) ) : '';
        $fixture->away_team   = isset( $post['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['away_team'][ $i ] ) ) ) : '';
        $fixture->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
        $fixture->final       = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;
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

    private function start_final_rounds( object $league ): bool {
        $updates       = false;
        $multiple_legs = false;
        $round_name    = $league->championship->get_final_keys( 1 );

        $fixture_args = array(
            'final'            => $round_name,
            'limit'            => false,
            'match_day'        => - 1,
            'reset_query_args' => true,
        );

        if ( $league->event->current_season['home_away'] ) {
            $multiple_legs       = true;
            $fixture_args['leg'] = 1;
        }

        $fixtures_list = array();
        $fixtures      = $league->get_matches( $fixture_args );

        foreach ( $fixtures as $fixture ) {
            $fixtures_list[] = $fixture->id;

            $home_team = $this->resolve_final_team_slot( $league, $fixture, (string) $fixture->home_team, 'home' );
            $away_team = $this->resolve_final_team_slot( $league, $fixture, (string) $fixture->away_team, 'away' );
            if ( null !== $home_team && null !== $away_team ) {
                $league->championship->set_teams( $fixture, $home_team, $away_team );
                $updates = true;
            }
        }

        $fixtures_list = $this->maybe_expand_fixtures_list_with_linked_fixtures( $fixtures_list, $multiple_legs );
        if ( $fixtures_list ) {
            $league->championship->update_final_results( $fixtures_list, array(), array(), array(), 1, $league->event->current_season['name'] );
        }

        return $updates;
    }

    private function resolve_final_team_slot( object $league, object $fixture, string $team_id, string $side ): ?int {
        $resolved_team_id = null;

        if ( '-1' === $team_id ) {
            $resolved_team_id = - 1;
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
                $resolved_team_id = - 1;
            } else {
                $resolved_team_id = $teams[0]->id;
            }
        }

        if ( null !== $resolved_team_id && - 1 !== $resolved_team_id ) {
            if ( 'home' === $side ) {
                $fixture->home_team     = $resolved_team_id;
                $fixture->teams['home'] = $league->get_team_dtls( $resolved_team_id );
            } else {
                $fixture->away_team     = $resolved_team_id;
                $fixture->teams['away'] = $league->get_team_dtls( $resolved_team_id );
            }
        }

        return $resolved_team_id;
    }

    private function maybe_expand_fixtures_list_with_linked_fixtures( array $fixtures_list, bool $multiple_legs ): array {
        if ( ! $multiple_legs || ! $fixtures_list ) {
            return $fixtures_list;
        }

        foreach ( $fixtures_list as $fixture_id ) {
            $fixture = get_match( $fixture_id );
            if ( $fixture && $fixture->linked_match ) {
                $fixtures_list[] = $fixture->linked_match;
            }
        }

        return $fixtures_list;
    }

    public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
        $league = get_league( $dto->league_id );
        if ( ! $league ) {
            return $this->result_league_not_found();
        }

        $post = $dto->post;

        $custom      = $post['custom'] ?? array();
        $fixtures    = $post['fixtures'] ?? array();
        $home_points = $post['home_points'] ?? array();
        $away_points = $post['away_points'] ?? array();
        $round       = isset( $post['round'] ) ? intval( $post['round'] ) : null;
        $season      = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;

        $league->championship->update_final_results( $fixtures, $home_points, $away_points, $custom, $round, $season );

        return new Action_Result_DTO( __( 'Final results updated', 'racketmanager' ), Admin_Message_Type::SUCCESS );
    }

    public function set_championship_fixtures( Draw_Action_Request_DTO $dto ): Action_Result_DTO {
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
        $build           = $this->build_championship_rounds_and_fixtures( $league, $season, $input_rounds, $num_first_round );
        if ( ! $build['valid'] ) {
            return new Action_Result_DTO( implode( '<br>', $build['messages'] ), Admin_Message_Type::ERROR );
        }

        $this->apply_championship_rounds_and_fixtures( $league, $season, $build['rounds'], $build['fixtures'], $event_season, $action );
        $message = ( 'replace' === $action ) ? __( 'Fixtures replaced', 'racketmanager' ) : __( 'Fixtures added', 'racketmanager' );

        return new Action_Result_DTO( $message, Admin_Message_Type::SUCCESS );
    }

    /**
     * @return array{valid:bool,messages:array<int,string>,rounds:array<string,object>,fixtures:array<string,array<int,object>>}
     */
    private function build_championship_rounds_and_fixtures( object $league, int $season, array $input_rounds, int $num_first_round ): array {
        $messages = array();
        $rounds   = array();
        $fixtures = array();
        $valid    = true;

        $next_round_date = null;
        $team_array      = array();

        foreach ( $input_rounds as $round ) {
            $round_number = intval( $round['round'] ?? 0 );
            $round_key    = strval( $round['key'] ?? '' );
            $round_date   = strval( $round['match_date'] ?? '' );
            $num_fixtures = intval( $round['num_matches'] ?? 0 );

            if ( '' === $round_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Fixture date missing for round %s', 'racketmanager' ), $round_number );
                $valid      = false;
                continue;
            }
            if ( ! empty( $next_round_date ) && $round_date >= $next_round_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Fixture date for round %s after next round date', 'racketmanager' ), $round_number );
                $valid      = false;
                continue;
            }

            $teams       = $league->championship->get_final_teams( $round_key );
            $round_build = $this->build_fixtures_for_round(
                $league,
                $season,
                array(
                    'key'          => $round_key,
                    'round'        => $round_number,
                    'fixture_date' => $round_date,
                    'num_fixtures' => $num_fixtures,
                ),
                $teams,
                $num_first_round,
                $team_array
            );

            $fixtures[ $round_date ] = $round_build['fixtures'];
            $rounds[ $round_key ]    = $this->build_round_meta( $round_key, $round_number, $round_date );
            $next_round_date         = $round_date;
        }

        return array(
            'valid'    => $valid,
            'messages' => $messages,
            'rounds'   => $rounds,
            'fixtures' => $fixtures,
        );
    }

    /**
     * @param array<string,object> $teams
     * @param array<int,int> $team_array
     *
     * @return array{fixtures:Fixture[]}
     */
    private function build_fixtures_for_round(
        object $league,
        int $season,
        array $round,
        array $teams,
        int $num_first_round,
        array &$team_array
    ): array {
        $fixtures = array();

        $round_key    = strval( $round['key'] ?? '' );
        $round_number = intval( $round['round'] ?? 0 );
        $round_date   = strval( $round['fixture_date'] ?? '' );
        $num_fixtures = intval( $round['num_fixtures'] ?? 0 );

        $first_round     = ( 1 === $round_number );
        $prev_round_name = null;
        $home_team       = 1;
        $away_team       = 2;

        if ( $first_round ) {
            $team_array = $this->get_first_round_team_array( $num_fixtures );
        } else {
            $prev_round      = $round_number - 1;
            $prev_round_name = $league->championship->get_final_keys( $prev_round );
        }

        for ( $i = 0; $i < $num_fixtures; ++ $i ) {
            $fixture = new Fixture();
            $fixture->set_date( $round_date . ' 00:00:00' );
            $fixture->set_match_day( null );

            $host = $this->get_round_host_for_fixture( $round_key, $round_number );
            if ( null !== $host ) {
                $fixture->set_host( $host );
            }

            [ $home_team_name, $away_team_name ] = $this->get_final_team_slot_names( $first_round, $team_array, $i, $num_first_round, $prev_round_name, $home_team, $away_team );

            if ( isset( $teams[ $home_team_name ] ) ) {
                $fixture->set_home_team( $teams[ $home_team_name ]->id );
            }
            if ( isset( $teams[ $away_team_name ] ) ) {
                $fixture->set_away_team( $teams[ $away_team_name ]->id );
            }

            if ( $first_round ) {
                ++ $home_team;
                $away_team = $num_first_round + 1 - $home_team;
            } else {
                $home_team += 2;
                $away_team += 2;
            }

            $fixture->set_location( null );
            $fixture->set_league_id( $league->id );
            $fixture->set_season( (string) $season );
            $fixture->set_final( $round_key );
            $fixture->set_custom( array( 'num_rubbers' => $league->num_rubbers ) );

            $fixtures[] = $fixture;
        }

        return array( 'fixtures' => $fixtures );
    }

    private function get_first_round_team_array( int $num_fixtures ): array {
        return match ( $num_fixtures ) {
            1 => array( 1 ),
            2 => array( 1, 3 ),
            4 => array( 1, 5, 3, 7 ),
            8 => array( 1, 9, 4, 12, 11, 14, 7, 15 ),
            16 => array( 1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31 ),
            32 => array( 1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63 ),
            default => array(),
        };
    }

    private function get_round_host_for_fixture( string $round_key, int $round_number ): ?string {
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

    private function build_round_meta( string $key, int $round_number, string $round_date ): stdClass {
        $round       = new stdClass();
        $round->name = $key;
        $round->num  = $round_number;
        $round->date = $round_date;

        return $round;
    }

    private function apply_championship_rounds_and_fixtures(
        object $league,
        int $season,
        array $rounds,
        array $fixtures,
        array $event_season,
        string $action
    ): void {
        $league->set_rounds( $season, $rounds );
        if ( 'replace' === $action ) {
            $this->fixture_service->delete_fixtures_for_season( $league->id, (string) $season );
        }

        $event_season['match_dates'] = array();
        foreach ( array_reverse( $fixtures ) as $fixture_date => $round_fixtures ) {
            $event_season['match_dates'][] = $fixture_date;
            foreach ( $round_fixtures as $fixture ) {
                $this->fixture_service->create_fixture( $fixture, $league );
            }
        }

        if ( $league->championship->is_consolation ) {
            return;
        }

        $event_season['num_match_days'] = count( $event_season['match_dates'] );
        $event                          = get_event( $league->event_id );
        if ( ! $event ) {
            return;
        }
        $event_seasons            = $event->get_seasons();
        $event_seasons[ $season ] = $event_season;
        $event->update_seasons( $event_seasons );
    }
}
