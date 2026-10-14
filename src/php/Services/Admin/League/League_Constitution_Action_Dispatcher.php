<?php
/**
 * League constitution action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_team;

/**
 * Handles constitution mutations for the league admin screen (delete teams, save constitution, promote/relegate, add teams, generate matches).
 */
readonly final class League_Constitution_Action_Dispatcher {

    public function __construct(
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param object|null          $event Event domain entity/model
     */
    public function handle( array $query, array $post, ?object $event = null ): Action_Result_DTO {
        if ( isset( $post['doActionConstitution'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_teams( $post );
        }

        if ( isset( $post['saveConstitution'] ) ) {
            return $this->handle_save_constitution( $post );
        }

        if ( isset( $post['promoteRelegate'] ) ) {
            return $this->handle_promote_relegate( $query, $post, $event );
        }

        if ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] ) {
            return $this->handle_add_teams( $post );
        }

        if ( isset( $post['generate_matches'] ) ) {
            return $this->handle_generate_matches( $query, $post, $event );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_teams( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'del_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $tables        = isset( $post['table'] ) && is_array( $post['table'] ) ? $post['table'] : array();
        $latest_season = isset( $post['latest_season'] ) ? sanitize_text_field( wp_unslash( strval( $post['latest_season'] ) ) ) : '';
        $messages      = array();

        foreach ( $tables as $table_id ) {
            $teams   = isset( $post['team_id'] ) && is_array( $post['team_id'] ) ? $post['team_id'] : array();
            $leagues = isset( $post['league_id'] ) && is_array( $post['league_id'] ) ? $post['league_id'] : array();
            $team    = $teams[ $table_id ] ?? null;
            $league  = $leagues[ $table_id ] ?? null;

            if ( null !== $team && null !== $league ) {
                $league_obj = get_league( intval( $league ) );
                $league_obj?->delete_team( intval( $team ), $latest_season );
                $messages[] = $team . ' ' . __( 'deleted', 'racketmanager' );
            }
        }

        if ( empty( $messages ) ) {
            return new Action_Result_DTO();
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_save_constitution( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $table_ids = isset( $post['table_id'] ) && is_array( $post['table_id'] ) ? $post['table_id'] : array();
        if ( empty( $table_ids ) ) {
            return new Action_Result_DTO();
        }

        $js                  = isset( $post['js-active'] ) && 1 === intval( $post['js-active'] );
        $rank                = 0;
        $updates             = 0;
        $latest_season       = isset( $post['latest_season'] ) ? sanitize_text_field( wp_unslash( strval( $post['latest_season'] ) ) ) : null;
        $constitution_action = isset( $post['constitutionAction'] ) ? sanitize_text_field( wp_unslash( strval( $post['constitutionAction'] ) ) ) : '';

        foreach ( $table_ids as $table_id ) {
            $team      = $post['team_id'][ $table_id ] ?? null;
            $league_id = isset( $post['league_id'][ $table_id ] ) ? intval( $post['league_id'][ $table_id ] ) : null;

            if ( $js ) {
                ++$rank;
            } else {
                $rank = isset( $post['rank'][ $table_id ] ) ? intval( $post['rank'][ $table_id ] ) : null;
            }

            $status  = isset( $post['status'][ $table_id ] ) ? sanitize_text_field( wp_unslash( strval( $post['status'][ $table_id ] ) ) ) : null;
            $profile = isset( $post['profile'][ $table_id ] ) ? sanitize_text_field( wp_unslash( strval( $post['profile'][ $table_id ] ) ) ) : null;

            if ( 'insert' === $constitution_action && null !== $league_id ) {
                $profile = '0';
                $league  = get_league( $league_id );
                $league?->add_team( $team, $latest_season, $rank, $status, $profile );
                ++$updates;
            } elseif ( 'update' === $constitution_action ) {
                $league_team = get_league_team( $table_id );
                if ( null !== $league_team && ( $league_id !== $league_team->league_id || $rank !== intval( $league_team->rank ) || $status !== $league_team->status || $profile !== $league_team->get_profile() ) ) {
                    $league_team->set_constitution_rank( $league_id, $rank, $status, $profile );
                    ++$updates;
                }
            }
        }

        if ( $updates > 0 ) {
            return new Action_Result_DTO(
                message: __( 'Updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'No updates', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_promote_relegate( array $query, array $post, ?object $event ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $table_ids = isset( $post['table_id'] ) && is_array( $post['table_id'] ) ? $post['table_id'] : array();
        if ( empty( $table_ids ) ) {
            return new Action_Result_DTO();
        }

        $js            = isset( $post['js-active'] ) && 1 === intval( $post['js-active'] );
        $rank          = 0;
        $valid         = true;
        $teams         = array();
        $latest_season = isset( $post['latest_season'] ) ? sanitize_text_field( wp_unslash( strval( $post['latest_season'] ) ) ) : null;
        $event_id      = isset( $post['event_id'] ) ? intval( $post['event_id'] ) : ( isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null );

        if ( null === $event && $event_id ) {
            $event = get_event( $event_id );
        }

        if ( null === $event ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        foreach ( $table_ids as $table_id ) {
            $status = isset( $post['status'][ $table_id ] ) ? sanitize_text_field( wp_unslash( strval( $post['status'][ $table_id ] ) ) ) : null;
            if ( empty( $rank ) && $status ) {
                $valid = false;
            }

            $team_id   = $post['team_id'][ $table_id ] ?? null;
            $league_id = $post['league_id'][ $table_id ] ?? null;
            $old_rank  = $post['old_rank'][ $table_id ] ?? null;

            if ( $js ) {
                ++$rank;
            } else {
                $rank = $post['rank'][ $table_id ] ?? '';
            }

            $team            = new stdClass();
            $team->team_id   = $team_id;
            $team->league_id = $league_id;
            $team->table_id  = $table_id;
            $team->rank      = $rank;
            $team->old_rank  = $old_rank;
            $team->status    = $status;
            $teams[]         = $team;
        }

        if ( ! $valid ) {
            return new Action_Result_DTO(
                message: __( 'Promotion and relegation has already occurred', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $result = $event->promote_and_relegate( $teams, $latest_season );
        if ( $result ) {
            return new Action_Result_DTO(
                message: __( 'Promotion and relegation actioned', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'Error with promotion and relegation', 'racketmanager' ),
            message_type: Admin_Message_Type::ERROR,
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_add_teams( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $teams = isset( $post['team'] ) && is_array( $post['team'] ) ? array_values( $post['team'] ) : array();
        if ( empty( $teams ) ) {
            return new Action_Result_DTO(
                message: __( 'No teams to add', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $league_id = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : 0;
        $league    = get_league( $league_id );
        $season    = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : '';
        $messages  = array();

        foreach ( $teams as $team_id ) {
            $league?->add_team( $team_id, $season, '99', 'NT', '1' );
            $team       = get_team( $team_id );
            $messages[] = ( $team?->title ?? $team_id ) . ' ' . __( 'added', 'racketmanager' );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_generate_matches( array $query, array $post, ?object $event ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $event_id = isset( $post['event_id'] ) ? intval( $post['event_id'] ) : ( isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null );
        $season   = isset( $post['latest_season'] ) ? intval( $post['latest_season'] ) : null;

        if ( ! $event_id ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( ! $season ) {
            return new Action_Result_DTO(
                message: __( 'No season set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( null === $event ) {
            $event = get_event( $event_id );
        }

        if ( null === $event ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $event->generate_box_league_matches();

        return new Action_Result_DTO(
            message: __( 'Matches generated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
