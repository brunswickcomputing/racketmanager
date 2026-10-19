<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;
use function __;

class Get_Event_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Competition_Shortcode_Presenter $competition_presenter
    ) {}

    public function handle( Get_Event_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event->set_season( $query->season );
        if ( empty( $event->current_season ) ) {
            return Shortcode_View_Model::for_error( __( 'Season not found for event', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $season          = $event->current_season['name'] ?? '';
        $seasons         = $event->get_seasons();
        $is_championship = (bool) ( $event->competition->is_championship ?? false );

        $tab = $query->tab;
        if ( empty( $tab ) ) {
            $tab = $is_championship ? 'draw' : 'standings';
        }

        $is_box          = ! empty( $event->is_box );
        $is_singular     = $query->is_singular;
        $competition_type = ( $event->competition->type instanceof \BackedEnum ) ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
        $event_seo_name   = Util::seo_url( (string) $event->name );

        $menu_options = [];
        if ( $is_championship ) {
            $menu_options['draw'] = [
                'name'        => 'draw',
                'selected'    => 'draw' === $tab,
                'available'   => true,
                'template'    => null,
                'description' => __( 'Draw', 'racketmanager' ),
            ];
            $menu_options['matches'] = [
                'name'        => 'matches',
                'selected'    => 'matches' === $tab,
                'available'   => true,
                'template'    => null,
                'description' => __( 'Matches', 'racketmanager' ),
            ];
        } else {
            $menu_options['standings'] = [
                'name'        => 'standings',
                'selected'    => 'standings' === $tab,
                'available'   => true,
                'template'    => null,
                'description' => __( 'Standings', 'racketmanager' ),
            ];
            if ( ! $is_box ) {
                $menu_options['clubs'] = [
                    'name'        => 'clubs',
                    'selected'    => 'clubs' === $tab,
                    'available'   => true,
                    'template'    => null,
                    'description' => __( 'Clubs', 'racketmanager' ),
                ];
            }
        }
        $menu_options['teams'] = [
            'name'        => 'teams',
            'selected'    => 'teams' === $tab,
            'available'   => true,
            'template'    => $is_championship ? 'list' : null,
            'description' => __( 'Teams', 'racketmanager' ),
        ];
        if ( ! $is_box ) {
            $menu_options['players'] = [
                'name'        => 'players',
                'selected'    => 'players' === $tab,
                'available'   => true,
                'template'    => null,
                'description' => __( 'Players', 'racketmanager' ),
            ];
        }

        $vars = [
            'event'            => $event,
            'seasons'          => $seasons,
            'curr_season'      => $season,
            'tab'              => $tab,
            'menu_options'     => $menu_options,
            'is_singular'      => $is_singular,
            'competition_type' => $competition_type,
            'event_seo_name'   => $event_seo_name,
        ];

        $vars['event_header'] = $this->competition_presenter->present_event_header(
            event: $event,
            curr_season: $season,
            seasons: $seasons,
            header_level: 1,
            post_id: $query->post_id,
            pagename_query: $query->pagename
        );

        return Shortcode_View_Model::for_template(
            'event',
            $vars,
            false
        );
    }
}
