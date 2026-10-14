<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use function is_numeric;
use function Racketmanager\un_seo_url;
use function __;

/**
 * Handler for resolving [competition-events] shortcode data into a view model.
 */
class Get_Competition_Events_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service
    ) {}

    public function handle( Get_Competition_Events_Query $query ): Shortcode_View_Model {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = un_seo_url( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( null !== $query->season ) {
            $competition->set_current_season( (string) $query->season );
        }

        $season_name = $competition->current_season['name'] ?? null;
        $season_int  = $query->season ?? ( ( null !== $season_name && is_numeric( $season_name ) ) ? (int) $season_name : null );

        try {
            $events = $this->competition_service->get_events_with_details_for_competition( (int) $competition->get_id(), $season_int );
        } catch ( Season_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        $template = '' !== $query->template ? 'events-' . $query->template : 'events';

        return new Shortcode_View_Model(
            template: $template,
            vars: [
                'competition' => $competition,
                'events'      => $events,
                'tab'         => 'events',
            ],
            template_type: 'competition'
        );
    }
}
