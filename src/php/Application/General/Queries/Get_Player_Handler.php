<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Player_Result;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use function __;
use function is_object;
use function method_exists;

/**
 * Handles retrieval of a player and related profile data.
 *
 * Resolves a player from a query by BTM identifier or player name, then builds
 * a player result containing club registrations, titles, career statistics, and
 * competition history grouped by competition type. Returns an error result when
 * no matching player can be found.
 */
readonly class Get_Player_Handler {
    /**
     * Initialises the service with player query, statistics, player management, registration, and tournament dependencies.
     *
     * @param Player_Query_Service_Interface      $player_query_service Service used to query player data.
     * @param Player_Stats_Service_Interface      $player_stats_service Service used to retrieve or manage player statistics.
     * @param Player_Service_Interface            $player_service Service used to manage player operations.
     * @param Registration_Service_Interface|null $registration_service Optional service used to manage registrations.
     * @param Tournament_Service_Interface|null   $tournament_service Optional service used to manage tournaments.
     *
     * @return void
     */
    public function __construct(
        private Player_Query_Service_Interface $player_query_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private Player_Service_Interface $player_service,
        private ?Registration_Service_Interface $registration_service = null,
        private ?Tournament_Service_Interface $tournament_service = null
    ) {}

    /**
     * Handles a player lookup query and builds the corresponding player result.
     *
     * Retrieves a player by BTM number or player name, loads related clubs, titles,
     * career statistics, and competition data, then returns a populated result. If
     * no matching player is found, an error result is returned.
     *
     * @param Get_Player_Query $query Query containing the player lookup criteria and template name.
     *
     * @return Player_Result Result containing the player details and related data, or an error result when not found.
     */
    public function handle( Get_Player_Query $query ): Player_Result {
        $player = null;
        if ( ! empty( $query->btm ) ) {
            $player = $this->player_service->find_player_by_btm( $query->btm );
        } elseif ( ! empty( $query->player_name ) ) {
            $player = $this->player_service->get_player_by_name( $query->player_name );
        }

        if ( ! is_object( $player ) ) {
            return Player_Result::error( __( 'Player not found', 'racketmanager' ) );
        }

        $player_id = method_exists( $player, 'get_id' ) ? (int) $player->get_id() : $player->ID ?? 0;

        $fallback_clubs = method_exists( $player, 'get_clubs' ) ? $player->get_clubs() : [];
        $player_clubs   = $this->registration_service
            ? $this->registration_service->get_clubs_for_player( $player_id )
            : $fallback_clubs;

        $titles = $this->player_service->get_titles_for_player( $player_id );

        $player->stats = $this->player_stats_service->get_career_stats( $player );

        $player_competitions = [];
        $competition_types   = [ 'cup', 'league', 'tournament' ];
        foreach ( $competition_types as $competition_type ) {
            if ( 'tournament' === $competition_type && $this->tournament_service ) {
                $player_competitions[ $competition_type ] = $this->tournament_service->get_tournaments_for_player( $player_id );
            } else {
                $player_competitions[ $competition_type ] = $this->player_query_service->get_competitions( $player, [ 'type' => $competition_type ] );
            }
        }

        return new Player_Result(
            player: $player,
            clubs: $player_clubs,
            titles: $titles,
            player_competitions: $player_competitions,
            template: $query->template
        );
    }
}
