<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Query;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function is_array;
use function is_numeric;
use function is_object;
use function str_replace;

/**
 * Driving adapter for Event shortcodes.
 */
class Event_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Event_Handler $event_handler,
        private readonly Get_Event_Standings_Handler $standings_handler,
        private readonly Get_Event_Draw_Handler $draw_handler,
        private readonly Get_Event_Matches_Handler $matches_handler,
        private readonly Get_Event_Clubs_Handler $clubs_handler,
        private readonly Get_Event_Teams_Handler $teams_handler,
        private readonly Get_Event_Players_Handler $players_handler,
        private readonly Get_Event_Partner_Handler $partner_handler,
        private readonly Get_Event_Team_Matches_Handler $team_matches_handler,
        private readonly Get_Team_Order_Players_Handler $team_order_players_handler,
        private readonly Get_Event_Dropdown_Handler $dropdown_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function show_event( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->event_handler->handle( $query ) );
    }

    public function show_event_standings( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Standings_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->standings_handler->handle( $query ) );
    }

    public function show_event_draw( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Draw_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->draw_handler->handle( $query ) );
    }

    public function show_event_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Matches_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->matches_handler->handle( $query ) );
    }

    public function show_event_clubs( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Clubs_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->clubs_handler->handle( $query ) );
    }

    public function show_event_teams( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Teams_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->teams_handler->handle( $query ) );
    }

    public function show_event_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Players_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->players_handler->handle( $query ) );
    }

    public function show_event_partner( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Partner_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->partner_handler->handle( $query ) );
    }

    public function show_event_team_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Team_Matches_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->team_matches_handler->handle( $query ) );
    }

    public function show_team_order_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Team_Order_Players_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->team_order_players_handler->handle( $query ) );
    }

    public function show_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Dropdown_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        return $this->view_presenter->render( $this->dropdown_handler->handle( $query ) );
    }

    private function extract_query_vars(): array {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function normalize_atts( array|string $atts, array $query_vars ): array {
        $atts_array = is_array( $atts ) ? $atts : [];

        if ( ! isset( $atts_array['id'] ) && ! isset( $atts_array['event_id'] ) ) {
            $event_id = $this->resolve_event_id( $atts_array, $query_vars );
            if ( $event_id ) {
                $atts_array['id'] = $event_id;
            } else {
                $event_name = $this->resolve_event_name( $atts_array, $query_vars );
                if ( $event_name ) {
                    $atts_array['event_name'] = $event_name;
                }
            }
        }

        return $atts_array;
    }

    private function resolve_event_id( array $atts, array $query_vars = [] ): ?int {
        if ( isset( $atts['id'] ) && is_numeric( $atts['id'] ) && (int) $atts['id'] > 0 ) {
            return (int) $atts['id'];
        }
        if ( isset( $atts['event_id'] ) && is_numeric( $atts['event_id'] ) && (int) $atts['event_id'] > 0 ) {
            return (int) $atts['event_id'];
        }
        if ( isset( $query_vars['event_id'] ) && is_numeric( $query_vars['event_id'] ) && (int) $query_vars['event_id'] > 0 ) {
            return (int) $query_vars['event_id'];
        }
        if ( isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) && ! empty( $GLOBALS['event']->id ) && is_numeric( $GLOBALS['event']->id ) ) {
            return (int) $GLOBALS['event']->id;
        }

        return null;
    }

    private function resolve_event_name( array $atts, array $query_vars = [] ): ?string {
        if ( ! empty( $atts['event_name'] ) ) {
            return (string) $atts['event_name'];
        }
        if ( ! empty( $atts['event'] ) ) {
            return (string) $atts['event'];
        }
        if ( ! empty( $query_vars['event'] ) ) {
            return str_replace( '-', ' ', (string) $query_vars['event'] );
        }
        if ( isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) && ! empty( $GLOBALS['event']->name ) ) {
            return (string) $GLOBALS['event']->name;
        }

        return null;
    }
}
