<?php
/**
 * Fixture API: Fixture class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Rubber_Repository;

/**
 * Class Fixture
 *
 * @Entity
 * @package Racketmanager\Domain
 */
class Fixture {

    /**
     * Fixture title
     *
     * @var string|null
     */
    public ?string $fixture_title = null;

    /**
     * Link
     *
     * @var string|null
     */
    public ?string $link = null;

    /**
     * Id
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * Group
     *
     * @var string|null
     */
    public ?string $group = null;

    /**
     * Date
     *
     * @var string|null
     */
    public ?string $date = null;

    /**
     * Date original
     *
     * @var string|null
     */
    public ?string $date_original = null;

    /**
     * Home team
     *
     * @var string|null
     */
    public ?string $home_team = null;

    /**
     * Away team
     *
     * @var string|null
     */
    public ?string $away_team = null;

    /**
     * Match day
     *
     * @var int|null
     */
    public ?int $match_day = null;

    /**
     * Location
     *
     * @var string|null
     */
    public ?string $location = null;

    /**
     * Host
     *
     * @var string|null
     */
    public ?string $host = null;

    /**
     * League id
     *
     * @var int|null
     */
    public ?int $league_id = null;

    /**
     * Season
     *
     * @var string|null
     */
    public ?string $season = null;

    /**
     * Home points
     *
     * @var string|null
     */
    public ?string $home_points = null;

    /**
     * Away points
     *
     * @var string|null
     */
    public ?string $away_points = null;

    /**
     * Winner id
     *
     * @var int|null
     */
    public ?int $winner_id = null;

    /**
     * Loser id
     *
     * @var int|null
     */
    public ?int $loser_id = null;

    /**
     * Status
     *
     * @var int|null
     */
    public ?int $status = null;

    /**
     * Linked fixture
     *
     * @var int|null
     */
    public ?int $linked_fixture = null;

    /**
     * Leg
     *
     * @var int|null
     */
    public ?int $leg = null;

    /**
     * Winner id tie
     *
     * @var int|null
     */
    public ?int $winner_id_tie = null;

    /**
     * Loser id tie
     *
     * @var int|null
     */
    public ?int $loser_id_tie = null;

    /**
     * Home points tie
     *
     * @var float|null
     */
    public ?float $home_points_tie = null;

    /**
     * Away points tie
     *
     * @var float|null
     */
    public ?float $away_points_tie = null;

    /**
     * Post id
     *
     * @var int|null
     */
    public ?int $post_id = null;

    /**
     * Final
     *
     * @var string|null
     */
    public ?string $final = null;

    /**
     * Custom
     *
     * @var array|null
     */
    public ?array $custom = null;

    /**
     * Updated user
     *
     * @var int|null
     */
    public ?int $updated_user = null;

    /**
     * Updated
     *
     * @var string|null
     */
    public ?string $updated = null;

    /**
     * Date result entered
     *
     * @var string|null
     */
    public ?string $date_result_entered = null;

    /**
     * Confirmed
     *
     * @var string|null
     */
    public ?string $confirmed = null;

    /**
     * Home captain
     *
     * @var int|null
     */
    public ?int $home_captain = null;

    /**
     * Away captain
     *
     * @var int|null
     */
    public ?int $away_captain = null;

    /**
     * Comments
     *
     * @var string|null
     */
    public ?string $comments = null;

    /**
     * Updated by
     *
     * @var string|null
     */
    public ?string $updated_by = null;

    /**
     * Start time
     *
     * @var string|null
     */
    public ?string $start_time = null;

    /**
     * Is walkover?
     *
     * @var bool
     */
    public bool $is_walkover = false;

    /**
     * Is retired
     *
     * @var bool
     */
    public bool $is_retired = false;

    /**
     * Is shared
     *
     * @var bool
     */
    public bool $is_shared = false;

    /**
     * Is withdrawn
     *
     * @var bool
     */
    public bool $is_withdrawn = false;

    /**
     * Is abandoned
     *
     * @var bool
     */
    public bool $is_abandoned = false;

    /**
     * Is cancelled
     *
     * @var bool
     */
    public bool $is_cancelled = false;

    /**
     * Is pending
     *
     * @var bool
     */
    public bool $is_pending = false;

    /**
     * Rubbers
     *
     * @var array|null
     */
    public ?array $rubbers = null;

    /**
     * Fixture constructor.
     *
     * @param object|null $fixture
     */
    public function __construct( ?object $fixture = null ) {
        if ( is_null( $fixture ) ) {
            return;
        }
        $this->id                  = $fixture->id ?? null;
        $this->group               = $fixture->group ?? null;
        $this->date                = $fixture->date ?? null;
        $this->date_original       = $fixture->date_original ?? null;
        $this->home_team           = $fixture->home_team ?? null;
        $this->away_team           = $fixture->away_team ?? null;
        $this->match_day           = $fixture->match_day ?? null;
        $this->location            = $fixture->location ?? null;
        $this->host                = $fixture->host ?? null;
        $this->league_id           = $fixture->league_id ?? null;
        $this->season              = $fixture->season ?? null;
        $this->home_points         = $fixture->home_points ?? null;
        $this->away_points         = $fixture->away_points ?? null;
        $this->winner_id           = $fixture->winner_id ?? null;
        $this->loser_id            = $fixture->loser_id ?? null;
        $this->status              = $fixture->status ?? null;
        $this->linked_fixture        = $fixture->linked_match ?? null;
        $this->leg                 = $fixture->leg ?? null;
        $this->winner_id_tie       = $fixture->winner_id_tie ?? null;
        $this->loser_id_tie        = $fixture->loser_id_tie ?? null;
        $this->home_points_tie     = $fixture->home_points_tie ?? null;
        $this->away_points_tie     = $fixture->away_points_tie ?? null;
        $this->post_id             = $fixture->post_id ?? null;
        $this->final               = $fixture->final ?? null;
        $this->custom              = maybe_unserialize( $fixture->custom ?? null );
        $this->updated_user        = $fixture->updated_user ?? null;
        $this->updated             = $fixture->updated ?? null;
        $this->date_result_entered = $fixture->date_result_entered ?? null;
        $this->confirmed           = $fixture->confirmed ?? null;
        $this->home_captain        = $fixture->home_captain ?? null;
        $this->away_captain        = $fixture->away_captain ?? null;
        $this->comments            = $fixture->comments ?? null;
        $this->updated_by          = $fixture->updated_by ?? null;
        $this->start_time          = $fixture->start_time ?? null;
        $this->set_status_flags();
    }

    /**
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function get_group(): ?string {
        return $this->group;
    }

    /**
     * @param string|null $group
     */
    public function set_group( ?string $group ): void {
        $this->group = $group;
    }

    /**
     * @return string|null
     */
    public function get_date(): ?string {
        return $this->date;
    }

    /**
     * @param string|null $date
     */
    public function set_date( ?string $date ): void {
        $this->date = $date;
    }

    /**
     * @return string|null
     */
    public function get_date_original(): ?string {
        return $this->date_original;
    }

    /**
     * @param string|null $date_original
     */
    public function set_date_original( ?string $date_original ): void {
        $this->date_original = $date_original;
    }

    /**
     * @return string|null
     */
    public function get_home_team(): ?string {
        return $this->home_team;
    }

    /**
     * @param string|null $home_team
     */
    public function set_home_team( ?string $home_team ): void {
        $this->home_team = $home_team;
    }

    /**
     * @return string|null
     */
    public function get_away_team(): ?string {
        return $this->away_team;
    }

    /**
     * @param string|null $away_team
     */
    public function set_away_team( ?string $away_team ): void {
        $this->away_team = $away_team;
    }

    /**
     * @return int|null
     */
    public function get_match_day(): ?int {
        return $this->match_day;
    }

    /**
     * @param int|null $match_day
     */
    public function set_match_day( ?int $match_day ): void {
        $this->match_day = $match_day;
    }

    /**
     * @return string|null
     */
    public function get_location(): ?string {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function set_location( ?string $location ): void {
        $this->location = $location;
    }

    /**
     * @return string|null
     */
    public function get_host(): ?string {
        return $this->host;
    }

    /**
     * @param string|null $host
     */
    public function set_host( ?string $host ): void {
        $this->host = $host;
    }

    /**
     * @return int|null
     */
    public function get_league_id(): ?int {
        return $this->league_id;
    }

    /**
     * @param int|null $league_id
     */
    public function set_league_id( ?int $league_id ): void {
        $this->league_id = $league_id;
    }

    /**
     * @return string|null
     */
    public function get_season(): ?string {
        return $this->season;
    }

    /**
     * @param string|null $season
     */
    public function set_season( ?string $season ): void {
        $this->season = $season;
    }

    /**
     * @return string|null
     */
    public function get_home_points(): ?string {
        return $this->home_points;
    }

    /**
     * @param string|null $home_points
     */
    public function set_home_points( ?string $home_points ): void {
        $this->home_points = $home_points;
    }

    /**
     * @return string|null
     */
    public function get_away_points(): ?string {
        return $this->away_points;
    }

    /**
     * @param string|null $away_points
     */
    public function set_away_points( ?string $away_points ): void {
        $this->away_points = $away_points;
    }

    /**
     * @return int|null
     */
    public function get_winner_id(): ?int {
        return $this->winner_id;
    }

    /**
     * @param int|null $winner_id
     */
    public function set_winner_id( ?int $winner_id ): void {
        $this->winner_id = $winner_id;
    }

    /**
     * @return int|null
     */
    public function get_loser_id(): ?int {
        return $this->loser_id;
    }

    /**
     * @param int|null $loser_id
     */
    public function set_loser_id( ?int $loser_id ): void {
        $this->loser_id = $loser_id;
    }

    /**
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->status;
    }

    /**
     * @param int|null $status
     */
    public function set_status( ?int $status ): void {
        $this->status = $status;
        $this->set_status_flags();
    }

    /**
     * @return int|null
     */
    public function get_linked_fixture(): ?int {
        return $this->linked_fixture;
    }

    /**
     * @param int|null $linked_fixture
     */
    public function set_linked_fixture( ?int $linked_fixture ): void {
        $this->linked_fixture = $linked_fixture;
    }

    /**
     * @return int|null
     */
    public function get_leg(): ?int {
        return $this->leg;
    }

    /**
     * @param int|null $leg
     */
    public function set_leg( ?int $leg ): void {
        $this->leg = $leg;
    }

    /**
     * @return int|null
     */
    public function get_winner_id_tie(): ?int {
        return $this->winner_id_tie;
    }

    /**
     * @param int|null $winner_id_tie
     */
    public function set_winner_id_tie( ?int $winner_id_tie ): void {
        $this->winner_id_tie = $winner_id_tie;
    }

    /**
     * @return int|null
     */
    public function get_loser_id_tie(): ?int {
        return $this->loser_id_tie;
    }

    /**
     * @param int|null $loser_id_tie
     */
    public function set_loser_id_tie( ?int $loser_id_tie ): void {
        $this->loser_id_tie = $loser_id_tie;
    }

    /**
     * @return float|null
     */
    public function get_home_points_tie(): ?float {
        return $this->home_points_tie;
    }

    /**
     * @param float|null $home_points_tie
     */
    public function set_home_points_tie( ?float $home_points_tie ): void {
        $this->home_points_tie = $home_points_tie;
    }

    /**
     * @return float|null
     */
    public function get_away_points_tie(): ?float {
        return $this->away_points_tie;
    }

    /**
     * @param float|null $away_points_tie
     */
    public function set_away_points_tie( ?float $away_points_tie ): void {
        $this->away_points_tie = $away_points_tie;
    }

    /**
     * @return int|null
     */
    public function get_post_id(): ?int {
        return $this->post_id;
    }

    /**
     * @param int|null $post_id
     */
    public function set_post_id( ?int $post_id ): void {
        $this->post_id = $post_id;
    }

    /**
     * @return string|null
     */
    public function get_final(): ?string {
        return $this->final;
    }

    /**
     * @param string|null $final
     */
    public function set_final( ?string $final ): void {
        $this->final = $final;
    }

    /**
     * @return array|null
     */
    public function get_custom(): ?array {
        return $this->custom;
    }

    /**
     * @param array|null $custom
     */
    public function set_custom( ?array $custom ): void {
        $this->custom = $custom;
    }

    /**
     * @return int|null
     */
    public function get_updated_user(): ?int {
        return $this->updated_user;
    }

    /**
     * @param int|null $updated_user
     */
    public function set_updated_user( ?int $updated_user ): void {
        $this->updated_user = $updated_user;
    }

    /**
     * @return string|null
     */
    public function get_updated(): ?string {
        return $this->updated;
    }

    /**
     * @param string|null $updated
     */
    public function set_updated( ?string $updated ): void {
        $this->updated = $updated;
    }

    /**
     * @return string|null
     */
    public function get_date_result_entered(): ?string {
        return $this->date_result_entered;
    }

    /**
     * @param string|null $date_result_entered
     */
    public function set_date_result_entered( ?string $date_result_entered ): void {
        $this->date_result_entered = $date_result_entered;
    }

    /**
     * @return string|null
     */
    public function get_confirmed(): ?string {
        return $this->confirmed;
    }

    /**
     * @param string|null $confirmed
     */
    public function set_confirmed( ?string $confirmed ): void {
        $this->confirmed = $confirmed;
    }

    /**
     * @return int|null
     */
    public function get_home_captain(): ?int {
        return $this->home_captain;
    }

    /**
     * @param int|null $home_captain
     */
    public function set_home_captain( ?int $home_captain ): void {
        $this->home_captain = $home_captain;
    }

    /**
     * @return int|null
     */
    public function get_away_captain(): ?int {
        return $this->away_captain;
    }

    /**
     * @param int|null $away_captain
     */
    public function set_away_captain( ?int $away_captain ): void {
        $this->away_captain = $away_captain;
    }

    /**
     * @return string|null
     */
    public function get_comments(): ?string {
        return $this->comments;
    }

    /**
     * @param string|null $comments
     */
    public function set_comments( ?string $comments ): void {
        $this->comments = $comments;
    }

    /**
     * @return string|null
     */
    public function get_updated_by(): ?string {
        return $this->updated_by;
    }

    /**
     * @param string|null $updated_by
     */
    public function set_updated_by( ?string $updated_by ): void {
        $this->updated_by = $updated_by;
    }

    /**
     * @return string|null
     */
    public function get_start_time(): ?string {
        return $this->start_time;
    }

    /**
     * @param string|null $start_time
     */
    public function set_start_time( ?string $start_time ): void {
        $this->start_time = $start_time;
    }

    /**
     * @return bool
     */
    public function is_walkover(): bool {
        return $this->is_walkover;
    }

    /**
     * @return bool
     */
    public function is_retired(): bool {
        return $this->is_retired;
    }

    /**
     * @return bool
     */
    public function is_shared(): bool {
        return $this->is_shared;
    }

    /**
     * @return bool
     */
    public function is_withdrawn(): bool {
        return $this->is_withdrawn;
    }

    /**
     * @return bool
     */
    public function is_abandoned(): bool {
        return $this->is_abandoned;
    }

    /**
     * @return bool
     */
    public function is_cancelled(): bool {
        return $this->is_cancelled;
    }

    /**
     * @return bool
     */
    public function is_pending(): bool {
        return $this->is_pending;
    }

    /**
     * @return string|null
     */
    public function get_walkover(): ?string {
        return $this->custom['walkover'] ?? null;
    }

    /**
     * @return string|null
     */
    public function get_retired(): ?string {
        return $this->custom['retired'] ?? null;
    }

    /**
     * @return string|null
     */
    public function get_shared(): ?string {
        return $this->custom['shared'] ?? null;
    }

    /**
     * Set status flags
     *
     * @return void
     */
    private function set_status_flags(): void {
        $this->is_walkover  = false;
        $this->is_shared    = false;
        $this->is_retired   = false;
        $this->is_abandoned = false;
        $this->is_cancelled = false;
        $this->is_withdrawn = false;
        $this->is_pending   = empty( $this->winner_id );

        if ( ! empty( $this->status ) ) {
            switch ( $this->status ) {
                case 1:
                    $this->is_walkover = true;
                    break;
                case 2:
                    $this->is_retired = true;
                    break;
                case 3:
                    $this->is_shared = true;
                    break;
                case 6:
                    $this->is_abandoned = true;
                    break;
                case 7:
                    $this->is_withdrawn = true;
                    break;
                case 8:
                    $this->is_cancelled = true;
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Reset finals data
     *
     * @param string $fixture_date
     * @return bool
     */
    public function reset_finals_data( string $fixture_date ): bool {
        $update = false;
        if ( $this->date !== $fixture_date ) {
            $this->date = $fixture_date;
            $update     = true;
        }
        if ( ! is_null( $this->location ) ) {
            $this->location = null;
            $update         = true;
        }
        return $update;
    }

    /**
     * Set finals data
     *
     * @param string $fixture_date
     * @param string $location
     * @return bool
     */
    public function set_finals_data( string $fixture_date, string $location ): bool {
        $update = false;
        if ( $this->date !== $fixture_date ) {
            $this->date = $fixture_date;
            $update     = true;
        }
        if ( $this->location !== $location ) {
            $this->location = $location;
            $update         = true;
        }
        return $update;
    }

    public function set_result( Result $result ): void {
        $this->home_points = (string) $result->get_home_points();
        $this->away_points = (string) $result->get_away_points();
        $this->winner_id   = $result->get_winner_id();
        $this->loser_id    = $result->get_loser_id();
        $this->status      = $result->get_status();
        $this->custom      = $result->get_custom();

        if ( ! empty( $result->get_sets() ) ) {
            $this->custom['sets'] = $result->get_sets();
        }

        $this->set_status_flags();
    }

    /**
     * Reset the fixture result and status.
     *
     * @return void
     */
    public function reset_result(): void {
        $this->home_points  = null;
        $this->away_points  = null;
        $this->winner_id    = 0;
        $this->loser_id     = 0;
        $this->status       = null;
        $this->custom       = array();
        $this->confirmed    = null;
        $this->home_captain = null;
        $this->away_captain = null;

        if ( ! empty( $this->leg ) && 2 === $this->leg ) {
            $this->home_points_tie = null;
            $this->away_points_tie = null;
            $this->winner_id_tie   = null;
            $this->loser_id_tie    = null;
        }

        $this->set_status_flags();
    }
    public function get_link(): ?string {
        return $this->link;
    }

    /**
     * Get rubbers for this fixture.
     *
     * @param int|null $player_id
     * @return Rubber[]
     */
    public function get_rubbers( ?int $player_id = null ): array {
        $rubber_repository = new Rubber_Repository();
        return $rubber_repository->find_by_fixture_id( (int) $this->id, $player_id );
    }
}
