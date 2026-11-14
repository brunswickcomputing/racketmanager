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
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Invoice_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Player_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use function is_array;

/**
 * Driving adapter for General shortcodes (Player, Invoice, Alert, Memberships, Favourites).
 */
readonly class General_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    /**
     * Initialises the shortcode controller with its required handlers, presenters, and optional services.
     *
     * @param RacketManager|null                 $racketmanager Racket manager instance, if available.
     * @param Shortcode_View_Presenter_Interface $view_presenter Presenter used to render shortcode views.
     * @param Get_Players_Handler                $players_handler Handler used to retrieve players.
     * @param Get_Player_Handler                 $player_handler Handler used to retrieve a single player.
     * @param Get_Favourites_Handler             $favourites_handler Handler used to retrieve favourites.
     * @param Get_Invoice_Handler                $invoice_handler Handler used to retrieve invoice data.
     * @param Get_Purchase_Order_Handler         $purchase_order_handler Handler used to retrieve purchase order data.
     * @param Get_Memberships_Handler            $memberships_handler Handler used to retrieve membership data.
     * @param Get_Player_Search_Handler          $player_search_handler Handler used to search players.
     * @param Get_Team_Order_Handler             $team_order_handler Handler used to retrieve team order data.
     * @param Get_Alert_Handler                  $alert_handler Handler used to retrieve alert data.
     * @param Player_Presenter_Interface         $player_presenter Presenter used to format player data.
     * @param Invoice_Presenter_Interface        $invoice_presenter Presenter used to format invoice data.
     * @param Template_Renderer_Interface|null   $template_renderer Optional template renderer.
     * @param Security_Service_Interface|null    $security_service Optional security service.
     * @param Query_Vars_Interface|null          $query_vars Optional query variables provider.
     *
     * @return void
     */
    public function __construct(
        private ?RacketManager $racketmanager,
        private Shortcode_View_Presenter_Interface $view_presenter,
        private Get_Players_Handler $players_handler,
        private Get_Player_Handler $player_handler,
        private Get_Favourites_Handler $favourites_handler,
        private Get_Invoice_Handler $invoice_handler,
        private Get_Purchase_Order_Handler $purchase_order_handler,
        private Get_Memberships_Handler $memberships_handler,
        private Get_Player_Search_Handler $player_search_handler,
        private Get_Team_Order_Handler $team_order_handler,
        private Get_Alert_Handler $alert_handler,
        private Player_Presenter_Interface $player_presenter,
        private Invoice_Presenter_Interface $invoice_presenter,
        private ?Template_Renderer_Interface $template_renderer = null,
        private ?Security_Service_Interface $security_service = null,
        private ?Query_Vars_Interface $query_vars = null
    ) {}

    /**
     * Renders the players directory shortcode output using the provided attributes, request data, and current user context.
     *
     * @param array|string $atts Shortcode attributes used to build the players query.
     *
     * @return string Rendered players directory output, or rendered error output when the query fails.
     */
    public function show_players( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : null ) ) : null;

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

        $player_vm = $this->player_presenter->present_players_directory(
            search_string: $result->search_string ?? '',
            search_results: $search_results,
            favourites: $result->favourites,
            template: $filename
        );

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() ) );
    }

    /**
     * Renders the player shortcode output for the supplied attributes.
     *
     * @param array|string $atts Shortcode attributes or raw attribute string used to build the player query.
     *
     * @return string Rendered player view output, or an error view when the player cannot be found.
     */
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

        $player_vm = $this->player_presenter->present_player(
            player: $result->player,
            clubs: $result->clubs,
            titles: $result->titles,
            player_competitions: $result->player_competitions,
            is_page_header: true,
            template: $filename
        );

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() ) );
    }

    /**
     * Renders the favourites shortcode output using the provided attributes and current request context.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to build the favourites query.
     *
     * @return string Rendered favourites view, or rendered error view when favourites retrieval fails.
     */
    public function show_favourites( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : null ) ) : null;

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

    /**
     * Displays an invoice shortcode output based on the provided attributes and request data.
     *
     * Builds an invoice query from shortcode attributes, query variables, and GET parameters,
     * retrieves the invoice data, and renders either an invoice template or an error view.
     *
     * @param array|string $atts Shortcode attributes used to identify and display the invoice.
     *
     * @return string Rendered invoice output or rendered error message.
     */
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

        $invoice_vm = $this->invoice_presenter->present_invoice(
            invoice: $result->invoice,
            billing: $result->billing,
            organisation_name: $result->organisation_name,
            site_url: $result->site_url,
            date_format: $result->date_format,
            is_user_logged_in: $result->is_user_logged_in,
            login_url: $result->login_url
        );

        $filename = ! empty( $result->template ) ? 'invoice-' . $result->template : 'invoice';

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'organisation_name' => $result->organisation_name,
                    'invoice'           => $invoice_vm,
                    'billing'           => $result->billing,
                    'invoice_number'    => $result->invoice_number,
                ]
            )
        );
    }

    /**
     * Displays a purchase order shortcode output based on the provided attributes and request data.
     *
     * Builds a purchase order query from shortcode attributes, query variables, and GET parameters,
     * retrieves the purchase order data, and renders either the purchase order modal template or an error view.
     *
     * @param array|string $atts Shortcode attributes used to identify and display the purchase order.
     *
     * @return string Rendered purchase order modal output or rendered error message.
     */
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

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'invoice' => $result->invoice,
                    'modal'   => $result->modal,
                ],
                'club'
            )
        );
    }

    /**
     * Displays membership shortcode output for the current or requested player.
     *
     * Determines the current authentication state, builds a memberships query from shortcode
     * attributes and request data, retrieves membership details, and renders either the
     * player club memberships template or an error view.
     *
     * @param array|string $atts Shortcode attributes used to identify and display memberships.
     *
     * @return string Rendered memberships output or rendered error message.
     */
    public function show_memberships( array|string $atts = [] ): string {
        $is_logged_in = $this->security_service ? $this->security_service->is_user_logged_in() : ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
        $user_id      = $is_logged_in ? ( $this->security_service ? $this->security_service->get_current_user_id() : ( function_exists( 'get_current_user_id' ) ? get_current_user_id() : null ) ) : null;

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

        $template_args['clubs']         = array_map( fn( $c ) => $this->player_presenter->present_club_membership( $c ), $result->clubs );
        $template_args['clubs_archive'] = array_map( fn( $c ) => $this->player_presenter->present_club_membership( $c ), $result->clubs_archive );
        $filename                       = ! empty( $result->template ) ? 'player-clubs-' . $result->template : 'player-clubs';

        return $this->view_presenter->render( Shortcode_View_Model::for_template( $filename, $template_args, 'account' ) );
    }

    /**
     * Displays a player search shortcode output based on the provided attributes and request data.
     *
     * Builds a player search query from shortcode attributes, query variables, and GET parameters,
     * retrieves matching player data, and renders either the players list template or an error view.
     *
     * @param array|string $atts Shortcode attributes used to configure and filter the player search.
     *
     * @return string Rendered player search results output or rendered error message.
     */
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

    /**
     * Displays team order shortcode output based on the provided attributes and request data.
     *
     * Builds a team order query from shortcode attributes, query variables, and GET parameters,
     * retrieves the required team order data, and renders either the selected team order template
     * or an error view.
     *
     * @param array|string $atts Shortcode attributes used to configure and display the team order.
     *
     * @return string Rendered team order output or rendered error message.
     */
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

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'clubs'       => $result->clubs,
                    'events'      => $result->events,
                    'event_types' => $result->event_types,
                    'age_groups'  => $result->age_groups,
                ]
            )
        );
    }

    /**
     * Displays an alert shortcode output based on the provided attributes and request data.
     *
     * Builds an alert query from shortcode attributes, query variables, and GET parameters,
     * retrieves the alert data, and renders the matching alert template.
     *
     * @param array|string $atts Shortcode attributes used to determine the alert message and display type.
     *
     * @return string Rendered alert output.
     */
    public function show_alert( array|string $atts = [] ): string {
        $query = Get_Alert_Query::from_atts(
            $this->normalize_atts( $atts ),
            $this->extract_query_vars(),
            $this->extract_get_params()
        );

        $result   = $this->alert_handler->handle( $query );
        $filename = ! empty( $result->template ) ? 'alert-' . $result->template : 'alert';

        return $this->view_presenter->render(
            Shortcode_View_Model::for_template(
                $filename,
                [
                    'msg'   => $result->msg,
                    'class' => $result->type,
                ]
            )
        );
    }

    /**
     * Loads and renders a template with the provided variables and optional template type.
     *
     * Returns an empty string when no template renderer is available.
     *
     * @param string       $template Template name or path to render.
     * @param array        $vars Variables to pass to the template.
     * @param false|string $template_type Optional template type or false to use the default handling.
     *
     * @return string Rendered template output, or an empty string if no renderer is available.
     */
    public function load_template( string $template, array $vars = [], false|string $template_type = false ): string {
        if ( $this->template_renderer ) {
            return $this->template_renderer->render( $template, $vars, $template_type );
        }

        return '';
    }

    /**
     * Extracts query variables from the configured query variable provider.
     *
     * Returns an empty array when no query variable provider is available.
     *
     * @return array Query variables keyed by name, or an empty array when unavailable.
     */
    private function extract_query_vars(): array {
        return $this->query_vars?->all() ?? [];
    }

    /**
     * Extracts GET request parameters from the current request.
     *
     * @return array GET parameters from the current request, or an empty array when none are available.
     */
    private function extract_get_params(): array {
        return $_GET ?? [];
    }

    /**
     * Normalises shortcode attributes to an array.
     *
     * Converts non-array attribute input to an empty array so downstream processing
     * can safely work with a consistent array structure.
     *
     * @param array|string $atts Shortcode attributes to normalize.
     *
     * @return array Normalized shortcode attributes.
     */
    private function normalize_atts( array|string $atts ): array {
        return is_array( $atts ) ? $atts : [];
    }
}
