<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Teams_Result;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use function is_numeric;
use function __;

/**
 * Handler for resolving [competition-teams] shortcode data into a view model.
 */
class Get_Competition_Teams_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Competition_Season_Service $competition_season_service
    ) {}

    public function handle( Get_Competition_Teams_Query $query ): Competition_Teams_Result {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Competition_Teams_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Seo_Slug::decode( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Teams_Result::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Competition_Teams_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_name = $competition->current_season['name'] ?? null;
        $season_int  = $query->season ?? ( ( null !== $season_name && is_numeric( $season_name ) ) ? (int) $season_name : null );

        try {
            $teams = $this->competition_service->get_teams_for_competition( (int) $competition->get_id(), $season_int );
        } catch ( Season_Not_Found_Exception $e ) {
            return Competition_Teams_Result::error( $e->getMessage() );
        }

        $competition->teams = $this->competition_service->get_teams_by_query(
            $competition,
            [
                'status'  => 1,
                'season'  => $season_name,
                'orderby' => [ 'name' => 'ASC' ],
            ]
        );

        $template = '' !== $query->template ? 'teams-' . $query->template : 'teams';

        return new Competition_Teams_Result(
            competition: $competition,
            teams: $teams ?? [],
            template: $template
        );
    }
}
