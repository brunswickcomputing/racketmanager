<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Ports;

use Racketmanager\Application\League\DTOs\League_View_State;
use Racketmanager\Domain\Competition\League;

/**
 * Port interface for League Query operations.
 */
interface League_Query_Service_Interface {
    /**
     * Get league teams.
     *
     * @param object $league     League object.
     * @param array  $query_args Query arguments.
     * @return array|int
     */
    public function get_league_teams( object $league, array $query_args = array() ): array|int;

    /**
     * Get team details in a league.
     *
     * @param object      $league  League object.
     * @param int         $team_id Team ID.
     * @param string|null $season  Season name.
     * @return object|false
     */
    public function get_team_dtls( object $league, int $team_id, ?string $season = null ): object|false;

    /**
     * Get league team object.
     *
     * @param object $league  League object.
     * @param int    $team_id Team ID.
     * @return object|false
     */
    public function get_league_team( object $league, int $team_id ): object|false;

    /**
     * Get crosstable field content.
     *
     * @param League $league      League entity.
     * @param int    $team_id     Team ID.
     * @param int    $opponent_id Opponent ID.
     * @return string
     */
    public function get_crosstable_field( League $league, int $team_id, int $opponent_id ): string;

    /**
     * Get matches for a league.
     *
     * @param mixed $league     League object or ID.
     * @param array $query_args Query arguments.
     * @return array|int|object
     */
    public function get_matches( mixed $league, array $query_args = array() ): array|int|object;

    /**
     * Get players in a league.
     *
     * @param object $league League object.
     * @param array  $args   Query arguments.
     * @return array|int
     */
    public function get_players( object $league, array $args = array() ): array|int;

    /**
     * Get rank of a team in a league season.
     *
     * @param object $league  League object.
     * @param int    $team_id Team ID.
     * @param string $season  Season name.
     * @return int|null
     */
    public function get_rank( object $league, int $team_id, string $season ): ?int;

    /**
     * Get status of a team in a league season.
     *
     * @param object $league  League object.
     * @param int    $team_id Team ID.
     * @param string $season  Season name.
     * @return string|null
     */
    public function get_status( object $league, int $team_id, string $season ): ?string;

    /**
     * Get number of teams.
     *
     * @param object      $league League object.
     * @param string|null $status Status filter.
     * @param bool        $latest Whether latest season.
     * @return int
     */
    public function get_num_teams( object $league, ?string $status = null, bool $latest = false ): int;

    /**
     * Set number of teams on view state.
     *
     * @param object $league League object.
     * @param bool   $total  Whether total.
     * @return League_View_State
     */
    public function set_num_teams( object $league, bool $total = false ): League_View_State;

    /**
     * Set number of matches on view state.
     *
     * @param object $league League object.
     * @param bool   $total  Whether total.
     * @return League_View_State
     */
    public function set_num_matches( object $league, bool $total = false ): League_View_State;

    /**
     * Set season for league query.
     *
     * @param object       $league          League object.
     * @param false|string $season          Season name or false.
     * @param bool         $force_overwrite Whether to force overwrite.
     * @param string|null  $final           Final round key.
     * @return void
     */
    public function set_season( object $league, false|string $season = false, bool $force_overwrite = false, ?string $final = null ): void; // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.finalFound

    /**
     * Set match day.
     *
     * @param object           $league    League object.
     * @param false|string|int $match_day Match day.
     * @return void
     */
    public function set_match_day( object $league, false|string|int $match_day = false ): void;

    /**
     * Set tab.
     *
     * @param object   $league     League object.
     * @param bool     $is_archive Whether archive.
     * @param int|null $tab        Tab index.
     * @return League_View_State
     */
    public function set_tab( object $league, bool $is_archive = false, ?int $tab = null ): League_View_State;

    /**
     * Resolve requested season.
     *
     * @param object $league League object.
     * @return false|string
     */
    public function resolve_requested_season( object $league ): false|string;

    /**
     * Resolve requested final round.
     *
     * @return string|null
     */
    public function resolve_requested_final(): ?string;

    /**
     * Resolve requested match day.
     *
     * @param object $league League object.
     * @return false|string|int
     */
    public function resolve_requested_match_day( object $league ): false|string|int;

    /**
     * Resolve requested page.
     *
     * @param object $league League object.
     * @param string $which  Which page type.
     * @return int
     */
    public function resolve_requested_page( object $league, string $which = 'matches' ): int;

    /**
     * Resolve requested tab.
     *
     * @param object $league League object.
     * @return int|null
     */
    public function resolve_requested_tab( object $league ): ?int;

    /**
     * Get page links.
     *
     * @param object     $league       League object.
     * @param string     $which        Which page type.
     * @param int|null   $current_page Current page number.
     * @param array|null $query_args   Query arguments.
     * @return string
     */
    public function get_page_links( object $league, string $which = 'matches', ?int $current_page = null, ?array $query_args = null ): string;

    /**
     * Add team to a league.
     *
     * @param object      $league  League object.
     * @param int|string  $team_id Team ID.
     * @param string      $season  Season name.
     * @param string|null $rank    Rank.
     * @param string|null $status  Status.
     * @param int|string  $profile Profile.
     * @return bool|int
     */
    public function add_team( object $league, int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int;
}
