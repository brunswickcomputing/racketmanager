<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Query;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function is_array;
use function is_object;

/**
 * Driving adapter for Tournament shortcodes.
 */
class Tournament_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Tournament_Handler $tournament_handler,
        private readonly Get_Tournament_Overview_Handler $overview_handler,
        private readonly Get_Tournament_Events_Handler $events_handler,
        private readonly Get_Tournament_Draws_Handler $draws_handler,
        private readonly Get_Tournament_Players_Handler $players_handler,
        private readonly Get_Tournament_Winners_Handler $winners_handler,
        private readonly Get_Tournament_Matches_Handler $matches_handler,
        private readonly Get_Tournament_Match_Handler $match_handler,
        private readonly Get_Order_Of_Play_Handler $order_of_play_handler,
        private readonly Get_Latest_Tournament_Handler $latest_tournament_handler,
        private readonly Get_Tournament_Withdrawal_Handler $withdrawal_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function show_tournament( array|string $atts = [] ): string {
        $query = Get_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->tournament_handler->handle( $query ) );
    }

    public function show_tournament_overview( array|string $atts = [] ): string {
        $query = Get_Tournament_Overview_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->overview_handler->handle( $query ) );
    }

    public function show_tournament_events( array|string $atts = [] ): string {
        $query = Get_Tournament_Events_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->events_handler->handle( $query ) );
    }

    public function show_draws( array|string $atts = [] ): string {
        $query = Get_Tournament_Draws_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->draws_handler->handle( $query ) );
    }

    public function show_tournament_players( array|string $atts = [] ): string {
        $query = Get_Tournament_Players_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->players_handler->handle( $query ) );
    }

    public function show_tournament_winners( array|string $atts = [] ): string {
        $query = Get_Tournament_Winners_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->winners_handler->handle( $query ) );
    }

    public function show_tournament_matches( array|string $atts = [] ): string {
        $query = Get_Tournament_Matches_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->matches_handler->handle( $query ) );
    }

    public function show_tournament_match( array|string $atts = [] ): string {
        $query = Get_Tournament_Match_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->match_handler->handle( $query ) );
    }

    public function show_order_of_play( array|string $atts = [] ): string {
        $query = Get_Order_Of_Play_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->order_of_play_handler->handle( $query ) );
    }

    public function show_latest_tournament( array|string $atts = [] ): string {
        $query = Get_Latest_Tournament_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->latest_tournament_handler->handle( $query ) );
    }

    public function show_tournament_withdrawal_modal( array|string $atts = [] ): string {
        $query = Get_Tournament_Withdrawal_Query::from_atts(
            $this->normalize_atts( $atts )
        );

        return $this->view_presenter->render( $this->withdrawal_handler->handle( $query ) );
    }

    public function show_tournament_withdrawal( array|string $atts = [] ): string {
        return $this->show_tournament_withdrawal_modal( $atts );
    }

    private function extract_query_vars(): array {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
