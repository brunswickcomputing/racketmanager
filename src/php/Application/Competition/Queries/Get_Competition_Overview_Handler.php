<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Overview_Result;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use function is_numeric;
use function __;

/**
 * Handler for resolving [competition-overview] shortcode data into a view model.
 */
class Get_Competition_Overview_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Competition_Season_Service $competition_season_service
    ) {}

    public function handle( Get_Competition_Overview_Query $query ): Competition_Overview_Result {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Competition_Overview_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Seo_Slug::decode( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Overview_Result::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Competition_Overview_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition_season = $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_name = $competition_season['name'] ?? null;
        $season_int  = $query->season ?? ( ( null !== $season_name && is_numeric( $season_name ) ) ? (int) $season_name : null );
        if ( null === $season_int ) {
            return Competition_Overview_Result::error( __( 'Season not found', 'racketmanager' ) );
        }

        try {
            $overview = $this->competition_service->get_competition_overview( (int) $competition->get_id(), $season_int );
        } catch ( Season_Not_Found_Exception $e ) {
            return Competition_Overview_Result::error( $e->getMessage() );
        }

        $template = '' !== $query->template ? 'overview-' . $query->template : 'overview';

        return new Competition_Overview_Result(
            competition: $competition,
            overview: $overview,
            competition_season: $competition_season,
            template: $template
        );
    }
}
