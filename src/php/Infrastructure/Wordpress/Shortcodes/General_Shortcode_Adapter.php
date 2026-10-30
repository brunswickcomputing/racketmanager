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
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
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
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Security_Service_Interface $security_service = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Invoice_Presenter $invoice_presenter = null
    ) {}

    public function show_players( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Players_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $is_logged_in,
            $user_id
        );

        $result = $this->players_handler->handle( $query );
        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $search_results = null;
        if ( ( ! empty( $result->players ) || ! empty( $result->search_string ) ) && $this->template_renderer ) {
            $search_results = $this->template_renderer->render( 'players-list', [ 'players' => $result->players ] );
        }

        $filename = ! empty( $result->template ) ? 'players-' . $result->template : 'players';

        $presenter = $this->player_presenter ?? new Player_Presenter();
        $player_vm = $presenter->present_players_directory(
            search_string: $result->search_string ?? '',
            search_results: $search_results,
            favourites: $result->favourites,
            template: $filename
        );

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() ) );
    }

    public function show_player( array|string $atts = [] ): string {
        $query = Get_Player_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->player_handler->handle( $query );
        if ( $result->is_error() || null === $result->player ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? __( 'Player not found', 'racketmanager' ) ) );
        }

        $filename = ! empty( $result->template ) ? 'player-' . $result->template : 'player';

        $presenter = $this->player_presenter ?? new Player_Presenter();
        $player_vm = $presenter->present_player(
            player: $result->player,
            clubs: $result->clubs,
            titles: $result->titles,
            player_competitions: $result->player_competitions,
            is_page_header: true,
            template: $filename
        );

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() ) );
    }

    public function show_favourites( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Favourites_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $is_logged_in,
            $user_id
        );

        $result = $this->favourites_handler->handle( $query );
        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $filename = ! empty( $result->template ) ? 'form-favourites-' . $result->template : 'form-favourites';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [ 'favourite_types' => $result->favourite_types ], 'form' ) );
    }

    public function show_invoice( array|string $atts = [] ): string {
        $query = Get_Invoice_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->invoice_handler->handle( $query );
        if ( $result->is_error() || null === $result->invoice ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? __( 'Invoice not found', 'racketmanager' ) ) );
        }

        $presenter  = $this->invoice_presenter ?? new Invoice_Presenter();
        $invoice_vm = $presenter->present_invoice(
            invoice: $result->invoice,
            billing: $result->billing,
            organisation_name: $result->organisation_name,
            site_url: $result->site_url,
            date_format: $result->date_format,
            is_user_logged_in: $result->is_user_logged_in,
            login_url: $result->login_url
        );

        $filename = ! empty( $result->template ) ? 'invoice-' . $result->template : 'invoice';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'organisation_name' => $result->organisation_name,
            'invoice'           => $invoice_vm,
            'billing'           => $result->billing,
            'invoice_number'    => $result->invoice_number,
        ] ) );
    }

    public function show_purchase_order( array|string $atts = [] ): string {
        $query = Get_Purchase_Order_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->purchase_order_handler->handle( $query );
        if ( $result->is_error() || null === $result->invoice ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? __( 'Invoice not found', 'racketmanager' ), 'modal' ) );
        }

        $filename = ! empty( $result->template ) ? 'purchase-order-modal-' . $result->template : 'purchase-order-modal';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'invoice' => $result->invoice,
            'modal'   => $result->modal,
        ], 'club' ) );
    }

    public function show_memberships( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null ) ) : null;

        $query = Get_Memberships_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params(),
            $is_logged_in,
            $user_id
        );

        $result = $this->memberships_handler->handle( $query );
        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? __( 'Player not found', 'racketmanager' ) ) );
        }

        $presenter = $this->player_presenter ?? new Player_Presenter();
        $template_args['clubs']         = array_map( fn( $c ) => $presenter->present_club_membership( $c ), $result->clubs );
        $template_args['clubs_archive'] = array_map( fn( $c ) => $presenter->present_club_membership( $c ), $result->clubs_archive );
        $filename                       = ! empty( $result->template ) ? 'player-clubs-' . $result->template : 'player-clubs';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $template_args, 'account' ) );
    }

    public function show_player_search( array|string $atts = [] ): string {
        $query = Get_Player_Search_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->player_search_handler->handle( $query );
        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $filename = ! empty( $result->template ) ? 'players-list-' . $result->template : 'players-list';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [ 'players' => $result->players ] ) );
    }

    public function show_team_order( array|string $atts = [] ): string {
        $query = Get_Team_Order_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->team_order_handler->handle( $query );
        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        $filename = ! empty( $result->template ) ? 'team-order-' . $result->template : 'team-order';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'clubs'       => $result->clubs,
            'events'      => $result->events,
            'event_types' => $result->event_types,
            'age_groups'  => $result->age_groups,
        ] ) );
    }

    public function show_alert( array|string $atts = [] ): string {
        $query = Get_Alert_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result = $this->alert_handler->handle( $query );
        $filename = ! empty( $result->template ) ? 'alert-' . $result->template : 'alert';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, [
            'msg'   => $result->msg,
            'class' => $result->type,
        ] ) );
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
