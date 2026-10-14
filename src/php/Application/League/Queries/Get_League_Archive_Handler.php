<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_object;
use function method_exists;

/**
 * Query handler for [leaguearchive] shortcode.
 */
class Get_League_Archive_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Competition_Service $competition_service,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function handle( Get_League_Archive_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( $query->league_name ) {
            $league = $this->league_service->get_league( $query->league_name );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $event = isset( $league->event_id ) ? $this->competition_service->get_event_by_id( (int) $league->event_id ) : null;
        if ( ! $event ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $seasons = is_object( $event ) && method_exists( $event, 'get_seasons' ) ? $event->get_seasons() : [];
        $leagues = is_object( $event ) && method_exists( $event, 'get_leagues' ) ? $event->get_leagues() : [];
        $tab     = $query->tab ?? 'standings';

        $league->set_tab( true );
        $league->set_templates( [
            'standings'  => $query->standings,
            'crosstable' => $query->crosstable,
            'matches'    => $query->matches,
            'teams'      => $query->teams,
        ] );
        $league->set_template( 'archive', $query->template );
        $league->matches_template_type = $query->matches_template_type;

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer && $this->template_renderer->template_exists( 'archive-' . $league->sport ) ) {
            $filename = 'archive-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'archive-' . $query->template : 'archive';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'  => $league,
                'seasons' => $seasons,
                'leagues' => $leagues,
                'tab'     => $tab,
            ],
            false
        );
    }
}
