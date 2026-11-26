<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competitions_List_Result;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use function __;

/**
 * Handles retrieval of competition lists for presentation.
 *
 * Coordinates competition, tournament, player, and player-query services to
 * resolve requested competition criteria, apply optional user-specific context,
 * and produce a list result suitable for rendering competition overview pages.
 */
readonly class Get_Competitions_Handler {
    /**
     * Initialises the service with required competition, tournament, player, and player query services.
     *
     * @param Competition_Service_Interface  $competition_service Competition service dependency.
     * @param Tournament_Service_Interface   $tournament_service Tournament service dependency.
     * @param Player_Service_Interface       $player_service Player service dependency.
     * @param Player_Query_Service_Interface $player_query_service Player query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service_Interface $competition_service,
        private Tournament_Service_Interface $tournament_service,
        private Player_Service_Interface $player_service,
        private Player_Query_Service_Interface $player_query_service
    ) {}

    /**
     * Handles a request to retrieve competitions for the specified query criteria.
     *
     * Decodes the requested competition type, applies optional age group filtering,
     * resolves the current player when a user ID is provided, and returns matching
     * competitions along with any competitions associated with that player.
     *
     * @param Get_Competitions_Query $query Query containing the competition type, optional age group, and optional user ID.
     *
     * @return Competitions_List_Result Result containing the competitions list, user-specific competitions, display type, competition type, and template, or an error result when the competition type is not set.
     */
    public function handle( Get_Competitions_Query $query ): Competitions_List_Result {
        if ( ! $query->type ) {
            return Competitions_List_Result::error( __( 'Competition type not set', 'racketmanager' ) );
        }

        $type = Seo_Slug::decode( $query->type );
        $args = [ 'type' => $type ];
        if ( $query->age_group ) {
            $args['age_group'] = $query->age_group;
        }
        $player = null;
        if ( $query->user_id ) {
            try {
                $player = $this->player_service->get_player( $query->user_id );
            } catch ( Player_Not_Found_Exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // A WordPress user need not have a player record.
            }
        }

        $user_competitions = [];
        if ( 'tournament' === $type ) {
            $args['orderby'] = [ 'date_end' => 'DESC' ];
            $competitions    = $this->tournament_service->get_tournaments( $args );
            foreach ( $competitions as $competition ) {
                $competition->set_type( $type );
            }
            if ( $player ) {
                $user_competitions = $this->tournament_service->get_tournaments_for_player( $player->get_id() );
            }
        } else {
            $competitions      = $this->competition_service->get_by_criteria( $args );
            $user_competitions = $player ? $this->player_query_service->get_competitions( $player, $args ) : [];
        }

        $competition_type = match ( $type ) {
            'league'     => __( 'Leagues', 'racketmanager' ),
            'cup'        => __( 'Cups', 'racketmanager' ),
            'tournament' => __( 'Tournaments', 'racketmanager' ),
            default      => __( 'Competitions', 'racketmanager' ),
        };

        return new Competitions_List_Result(
            competitions: $competitions,
            user_competitions: $user_competitions,
            type: $competition_type,
            competition_type: $type,
            template: 'competitions'
        );
    }
}
