<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for fixture details and presentation preparation.
 */
interface Fixture_Detail_Service_Interface {
    /**
     * Get tournament fixtures for a specific player.
     *
     * @param int|null $player_id player id.
     * @param int|null $tournament_id tournament id.
     *
     * @return Fixture_Details_DTO[]
     */
    public function get_fixtures_for_player_for_tournament( ?int $player_id, ?int $tournament_id ): array;

    /**
     * Retrieve fixture details DTO.
     *
     * @param int|Fixture|null $fixture_id fixture id.
     * @param bool             $is_tournament whether fixture is tournament.
     * @param int|null         $rubber_player_id rubber player id.
     *
     * @return Fixture_Details_DTO|null
     */
    public function get_fixture_with_details( int|Fixture|null $fixture_id, bool $is_tournament = false, ?int $rubber_player_id = null ): ?Fixture_Details_DTO;

    /**
     * Retrieve tournament fixture details DTO.
     *
     * @param int|Fixture|null $fixture_id fixture id.
     *
     * @return Fixture_Details_DTO|null
     */
    public function get_tournament_fixture_with_details( int|Fixture|null $fixture_id ): ?Fixture_Details_DTO;

    /**
     * Resolve placeholder title for knockouts.
     *
     * @param string      $team_ref team reference.
     * @param string      $season season.
     * @param object      $league league.
     * @param string|null $fixture_final fixture final.
     *
     * @return string|null
     */
    public function resolve_placeholder_title( string $team_ref, string $season, object $league, ?string $fixture_final = null ): ?string;

    /**
     * Retrieve previous fixture for tournament progression.
     *
     * @param Fixture         $fixture fixture.
     * @param string|int|null $team_ref team reference.
     *
     * @return Fixture|null
     */
    public function get_previous_fixture( Fixture $fixture, string|int|null $team_ref ): ?Fixture;

    /**
     * Collect player IDs associated with a fixture.
     *
     * @param int|Fixture|Fixture_Details_DTO|null $fixture_id_or_entity fixture id or entity.
     *
     * @return int[]
     */
    public function collect_fixture_player_ids( int|Fixture|Fixture_Details_DTO|null $fixture_id_or_entity ): array;

    /**
     * Get team name or placeholder label.
     *
     * @param mixed       $team_id team id.
     * @param string      $season season.
     * @param object      $league league.
     * @param string|null $final_round final.
     *
     * @return string
     */
    public function get_team_name_or_placeholder( mixed $team_id, string $season, object $league, ?string $final_round ): string;
}
