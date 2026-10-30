<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use BackedEnum;
use Racketmanager\Application\Event\DTOs\Event_Page_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;
use function __;

class Get_Event_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Query $query ): Event_Page_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Page_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Page_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );
        if ( empty( $event->current_season ) ) {
            return Event_Page_Result::error( __( 'Season not found for event', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Event_Page_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $season          = isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : '';
        $seasons         = $event->get_seasons();
        $is_championship = (bool) ( $event->competition->is_championship ?? false );

        $tab = $query->tab;
        if ( empty( $tab ) ) {
            $tab = $is_championship ? 'draw' : 'standings';
        }

        $is_box          = ! empty( $event->is_box );
        $is_singular     = $query->is_singular;
        $competition_type = ( $event->competition->type instanceof BackedEnum ) ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
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

        return new Event_Page_Result(
            event: $event,
            seasons: $seasons,
            curr_season: $season,
            tab: $tab,
            menu_options: $menu_options,
            is_singular: $is_singular,
            competition_type: $competition_type,
            event_seo_name: $event_seo_name,
            post_id: $query->post_id,
            pagename: $query->pagename,
            template: 'event'
        );
    }
}
