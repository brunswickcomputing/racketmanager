<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Query;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Query;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Query;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Query;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Query;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function is_array;
use function is_object;

/**
 * Driving adapter for General shortcodes (Player, Invoice, Alert, Memberships, Favourites).
 */
class General_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_Players_Handler $players_handler,
        private readonly Get_Player_Handler $player_handler,
        private readonly Get_Favourites_Handler $favourites_handler,
        private readonly Get_Invoice_Handler $invoice_handler,
        private readonly Get_Purchase_Order_Handler $purchase_order_handler,
        private readonly Get_Memberships_Handler $memberships_handler,
        private readonly Get_Player_Search_Handler $player_search_handler,
        private readonly Get_Team_Order_Handler $team_order_handler,
        private readonly Get_Alert_Handler $alert_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function show_players( array|string $atts = [] ): string {
        $query = Get_Players_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->players_handler->handle( $query ) );
    }

    public function show_player( array|string $atts = [] ): string {
        $query = Get_Player_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->player_handler->handle( $query ) );
    }

    public function show_favourites( array|string $atts = [] ): string {
        $query = Get_Favourites_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->favourites_handler->handle( $query ) );
    }

    public function show_invoice( array|string $atts = [] ): string {
        $query = Get_Invoice_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->invoice_handler->handle( $query ) );
    }

    public function show_purchase_order( array|string $atts = [] ): string {
        $query = Get_Purchase_Order_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->purchase_order_handler->handle( $query ) );
    }

    public function show_memberships( array|string $atts = [] ): string {
        $query = Get_Memberships_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->memberships_handler->handle( $query ) );
    }

    public function show_player_search( array|string $atts = [] ): string {
        $query = Get_Player_Search_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->player_search_handler->handle( $query ) );
    }

    public function show_team_order( array|string $atts = [] ): string {
        $query = Get_Team_Order_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->team_order_handler->handle( $query ) );
    }

    public function show_alert( array|string $atts = [] ): string {
        $query = Get_Alert_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        return $this->view_presenter->render( $this->alert_handler->handle( $query ) );
    }

    public function load_template( string $template, array $vars = [], false|string $template_type = false ): string {
        if ( $this->template_renderer ) {
            return $this->template_renderer->render( $template, $vars, $template_type );
        }

        return '';
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
