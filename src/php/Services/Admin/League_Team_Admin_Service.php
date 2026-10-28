<?php
declare( strict_types=1 );
/**
 * League Team Admin Service
 *
 * Application-layer team operations for admin screens (list addable teams, add, delete, withdraw, rank).
 * Delegates to League_Service / Team_Service so that no domain rules are duplicated here.
 *
 * @package RacketManager
 * @subpackage Services/Admin
 */

namespace Racketmanager\Services\Admin;

use InvalidArgumentException;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use RuntimeException;

readonly class League_Team_Admin_Service {

    public function __construct(
        private League_Service $league_service,
        private Team_Service $team_service,
        private Team_Repository_Interface $team_repository,
    ) {
    }

    /**
     * Get teams that can be added to a league (player entry, club or consolation rules).
     *
     * @param League     $league League (domain) object.
     * @param string|int $season Season.
     *
     * @return array
     */
    public function get_addable_teams( League $league, string|int $season = '' ): array {
        $league_type = (string) ( $league->type ?? '' );
        if ( 'LD' === $league_type ) {
            $league_type = 'XD';
        }

        if ( $league->championship?->is_consolation() ) {
            return $this->league_service->get_consolation_teams( $league, (int) $season );
        }
        if ( ! empty( $league->event->competition->is_player_entry ) ) {
            return $this->team_service->get_player_teams( $league_type );
        }
        return $this->team_service->get_club_teams( $league_type );
    }

    /**
     * Add teams to a league, one at a time, so a missing team does not stop the others.
     *
     * @param object            $league   League object.
     * @param array<int,string> $team_ids Posted team ids.
     * @param int               $season   Season.
     *
     * @return array{count:int, messages:string[], errors:string[]}
     */
    public function add_teams( object $league, array $team_ids, int $season ): array {
        $league_id = method_exists( $league, 'get_id' ) ? (int) $league->get_id() : (int) ( $league->id ?? 0 );
        $result    = array(
            'count'    => 0,
            'messages' => array(),
            'errors'   => array(),
        );
        foreach ( $team_ids as $team_id ) {
            if ( ! is_numeric( $team_id ) ) {
                $result['errors'][] = __( 'Team not found', 'racketmanager' );
                continue;
            }
            try {
                $this->league_service->add_team_to_league( (int) $team_id, $league_id, $season );
                ++$result['count'];
                $result['messages'][] = __( 'Team added', 'racketmanager' );
            } catch ( Team_Not_Found_Exception | League_Not_Found_Exception $e ) {
                $result['errors'][] = $e->getMessage();
            }
        }
        return $result;
    }

    /**
     * Delete teams from a league. Stops at the first failure (legacy behaviour).
     *
     * @param object            $league   League object.
     * @param array<int,string> $team_ids Posted team ids.
     * @param string|null       $season   Season.
     *
     * @return array{count:int, messages:string[], errors:string[]}
     */
    public function delete_teams( object $league, array $team_ids, ?string $season = null ): array {
        $result = array(
            'count'    => 0,
            'messages' => array(),
            'errors'   => array(),
        );
        try {
            foreach ( $team_ids as $team_id ) {
                $this->league_service->delete_team( $league, (int) $team_id, $season );
                ++$result['count'];
                $result['messages'][] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
            }
        } catch ( RuntimeException | InvalidArgumentException $e ) {
            $result['errors'][] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Withdraw teams from a league. Stops at the first failure (legacy behaviour).
     *
     * @param object            $league   League object.
     * @param array<int,string> $team_ids Posted team ids.
     * @param string            $season   Season.
     *
     * @return array{count:int, messages:string[], errors:string[]}
     */
    public function withdraw_teams( object $league, array $team_ids, string $season ): array {
        $result = array(
            'count'    => 0,
            'messages' => array(),
            'errors'   => array(),
        );
        try {
            foreach ( $team_ids as $team_id ) {
                $team = $this->team_repository->find_by_id( (int) $team_id );
                $this->league_service->withdraw_team( $league, (int) $team_id, $season );
                ++$result['count'];
                $title                = $team->title ?? (string) $team_id;
                $result['messages'][] = $title . ' ' . __( 'withdrawn', 'racketmanager' );
            }
        } catch ( RuntimeException | InvalidArgumentException $e ) {
            $result['errors'][] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Rank teams in a league.
     *
     * @param object               $league   League object.
     * @param string               $type     Ranking mode.
     * @param array<int,string>    $team_ids Team ids.
     * @param array<string, mixed> $post     Posted data.
     *
     * @return bool
     */
    public function rank_teams( object $league, string $type, array $team_ids, array $post = array() ): bool {
        return (bool) $this->league_service->rank_teams( $league, $type, $post, $team_ids );
    }
}
