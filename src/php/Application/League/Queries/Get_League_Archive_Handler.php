<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function function_exists;
use function is_object;
use function method_exists;

/**
 * Query handler for [leaguearchive] shortcode.
 */
class Get_League_Archive_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Competition_Service $competition_service,
        private readonly ?Shortcode_Template_Renderer $template_renderer,
        private readonly League_Archive_Presenter $archive_presenter,
        private readonly Event_Query_Service $event_query_service,
        private readonly ?League_Query_Service $league_query_service = null,
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

        $league_query_service = $this->league_query_service ?? new League_Query_Service( $this->event_query_service->get_league_repository() );
        if ( false !== $league_query_service->resolve_requested_season( $league ) ) {
            $league_query_service->set_season( $league );
        }
        $seasons = is_object( $event ) && method_exists( $event, 'get_seasons' ) ? $event->get_seasons() : [];
        $leagues = $event instanceof \Racketmanager\Domain\Competition\Event ? $this->event_query_service->get_leagues( $event ) : [];
        $tab     = $query->tab ?? 'standings';

        $league_query_service->set_tab( $league, true );
        $league->set_templates( [
            'standings'  => $query->standings,
            'crosstable' => $query->crosstable,
            'matches'    => $query->matches,
            'teams'      => $query->teams,
        ] );
        $league->set_template( 'archive', $query->template );
        $league->matches_template_type = $query->matches_template_type;

        $archive_vm = $this->archive_presenter->present_archive(
            league: $league,
            event: $event,
            seasons: $seasons,
            leagues: $leagues,
            tab: $tab,
            post_id: $query->post_id
        );

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer && $this->template_renderer->template_exists( 'archive-' . $league->sport ) ) {
            $filename = 'archive-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'archive-' . $query->template : 'archive';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'archive_vm'          => $archive_vm,
                'vm'                  => $archive_vm,
                'league'              => $league,
                'seasons'             => $seasons,
                'leagues'             => $leagues,
                'tab'                 => $tab,
                'post_id'             => $archive_vm->post_id,
                'pagename'            => $archive_vm->pagename,
                'form_action'         => $archive_vm->form_action,
                'competition_type'    => $archive_vm->competition_type,
                'league_seo_name'     => $archive_vm->league_seo_name,
                'icon_path'           => $archive_vm->icon_path,
                'icon_url'            => $archive_vm->icon_url,
                'calendar_icon_url'   => $archive_vm->calendar_icon_url,
                'event_name'          => $archive_vm->event_name,
                'event_url'           => $archive_vm->event_url,
                'competition_name'    => $archive_vm->competition_name,
                'competition_url'     => $archive_vm->competition_url,
                'current_season_name' => $archive_vm->current_season_name,
                'date_start_display'  => $archive_vm->date_start_display,
                'date_end_display'    => $archive_vm->date_end_display,
                'has_date_range'      => $archive_vm->has_date_range,
                'season_title'        => $archive_vm->season_title,
                'season_selection'    => $archive_vm->season_selection,
                'season_options'      => $archive_vm->season_options,
                'league_options'      => $archive_vm->league_options,
                'favourite_id'        => $archive_vm->favourite_id,
                'favourite_type'      => $archive_vm->favourite_type,
                'is_championship'     => $archive_vm->is_championship,
                'championship_season' => $archive_vm->championship_season,
                'menu_options'        => $archive_vm->menu_options,
            ],
            false
        );
    }
}
