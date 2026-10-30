<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Query;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Query;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Query;
use Racketmanager\Application\Club\Queries\Get_Club_Query;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Query;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Query;
use Racketmanager\Application\Event\Queries\Event_Allowlist_Guard;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util_Lookup;
use function current_user_can;
use function get_query_var;
use function is_user_logged_in;
use function shortcode_atts;
use function wp_get_current_user;
use function is_array;
use function array_map;
use function function_exists;
use function __;

/**
 * Driving adapter for Club shortcodes.
 *
 * Shortcode attributes and WordPress request state (query vars, current user) are translated into
 * typed queries; query handlers produce a view model (or error) that a presenter renders:
 * - [club]: Get_Club_Handler -> Club_Shortcode_Presenter
 * - [club-players], [club-roles], [club-role-modal], [club-competitions], [club-invoices]:
 *   Get_Club_*_Handler -> Shortcode_View_Presenter
 * [clubs], [club-event], [club-team] and [team-edit] are resolved here from Club_Service and the
 * event/team lookups, and rendered through the same presenter.
 */
class Club_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Get_Club_Handler $get_club_handler,
        private readonly Club_Shortcode_Presenter $club_shortcode_presenter,
        private readonly Get_Club_Players_Handler $players_handler,
        private readonly Get_Club_Roles_Handler $roles_handler,
        private readonly Get_Club_Role_Modal_Handler $role_modal_handler,
        private readonly Get_Club_Competitions_Handler $competitions_handler,
        private readonly Get_Club_Invoices_Handler $invoices_handler,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Competition_Shortcode_Presenter $competition_shortcode_presenter,
        private readonly Event_Query_Service $event_query_service,
        private readonly Event_Repository_Interface $event_repository,
        private readonly Team_Repository_Interface $team_repository,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Invoice_Presenter $invoice_presenter = null,
        private readonly ?Team_Details_Presenter $team_details_presenter = null
    ) {
    }

    /**
     * [clubs template=X]
     */
    public function show_clubs( array|string $atts = [] ): string {
        $template = $this->template_attribute( $atts );
        $clubs    = $this->club_service->get_clubs_with_details( [ 'type' => 'current' ] );

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $template ? 'clubs-' . $template : 'clubs',
                vars: [
                    'clubs'                => $clubs,
                    'user_can_update_club' => false,
                    'standalone'           => false,
                ]
            )
        );
    }

    /**
     * [club id=ID template=X]
     */
    public function show_club( array|string $atts = [] ): string {
        global $wp;
        $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
        $query      = Get_Club_Query::from_atts( $atts, $query_vars );

        $result = $this->get_club_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->club_shortcode_presenter->render( Club_View_Model::for_error( $result->error_message ?? '' ) );
        }

        if ( ! $result->club_details ) {
            return $this->club_shortcode_presenter->render( Club_View_Model::for_error( __( 'Club not found', 'racketmanager' ) ) );
        }

        $presenter = $this->club_presenter ?? new Club_Presenter();
        $club_vm   = $presenter->present(
            club: $result->club_details,
            standalone: true,
            google_maps_key: $result->google_maps_key,
            user_can_update_club: $result->user_can_update_club,
            template: $result->template
        );

        return $this->club_shortcode_presenter->render( $club_vm );
    }

    /**
     * [club-players template=X]; the player is taken from the player_id query var.
     */
    public function show_club_players( array|string $atts = [] ): string {
        $player_name = get_query_var( 'player_id' );
        $referrer    = function_exists( 'wp_get_referer' ) ? wp_get_referer() : false;
        $query       = new Get_Club_Players_Query(
            club_name: $this->club_name_from_request(),
            player_name: $player_name ? Seo_Slug::decode( (string) $player_name ) : null,
            template: $this->template_attribute( $atts ),
            referrer: $referrer ?: null
        );

        $result = $this->players_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::error( $result->error_message ?? '' ) );
        }

        $club_presenter   = $this->club_presenter ?? new Club_Presenter();
        $player_presenter = $this->player_presenter ?? new Player_Presenter();

        $player_vm = null;
        $players   = [];

        if ( $result->club_player ) {
            $player_vm = $player_presenter->present_player(
                player: $result->club_player,
                clubs: [ $result->club_player ]
            );
        } else {
            $players = array_map(
                fn( $p ) => $club_presenter->present_roster_player( $p, $result->club, $result->use_wtn ),
                $result->raw_players
            );
        }

        $club_players_url = $result->club ? $club_presenter->get_link( $result->club, 'players' ) : '';
        $page_referrer    = $result->referrer ?: $club_players_url;

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $result->template ? 'players-' . $result->template : 'players',
                vars: [
                    'club'             => $result->club,
                    'user_can_update'  => $result->user_can_update,
                    'players'          => $players,
                    'player'           => $result->club_player,
                    'player_vm'        => $player_vm,
                    'display_opt'      => $result->display_opt,
                    'use_wtn'          => $result->use_wtn,
                    'rating_header'    => $result->use_wtn ? __( 'WTN', 'racketmanager' ) : __( 'Rating Points', 'racketmanager' ),
                    'club_players_url' => $club_players_url,
                    'page_referrer'    => $page_referrer,
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [club-roles template=X]
     */
    public function show_club_roles( array|string $atts = [] ): string {
        $query = new Get_Club_Roles_Query(
            club_name: $this->club_name_from_request(),
            template: $this->template_attribute( $atts )
        );

        $result = $this->roles_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::error( $result->error_message ?? '' ) );
        }

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $result->template ? 'roles-' . $result->template : 'roles',
                vars: [
                    'club'  => $result->club,
                    'roles' => $result->roles,
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [club-role-modal id=role_ID modal=X template=X]
     */
    public function show_club_role_modal( array|string $atts = [] ): string {
        $args  = shortcode_atts(
            [
                'id'       => 0,
                'modal'    => null,
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );
        $query = new Get_Club_Role_Modal_Query(
            role_id: (int) $args['id'],
            modal: $args['modal'],
            template: (string) $args['template']
        );

        $result = $this->role_modal_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::error( $result->error_message ?? '', $result->error_variant ) );
        }

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $result->template ? 'club-role-modal-' . $result->template : 'club-role-modal',
                vars: [
                    'club_role'  => $result->club_role,
                    'modal'      => $result->modal,
                    'club_roles' => $result->club_roles,
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [club-competitions template=X]; the competition is taken from the competition_name query var.
     */
    public function show_club_competitions( array|string $atts = [] ): string {
        $competition_name = get_query_var( 'competition_name' );
        $query            = new Get_Club_Competitions_Query(
            club_name: $this->club_name_from_request(),
            competition_name: $competition_name ? Seo_Slug::decode( (string) $competition_name ) : null,
            template: $this->template_attribute( $atts )
        );

        $result = $this->competitions_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::error( $result->error_message ?? '' ) );
        }

        $club_presenter         = $this->club_presenter ?? new Club_Presenter();
        $team_details_presenter = $this->team_details_presenter ?? new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );

        $competitions = [];
        foreach ( $result->competitions_data as $comp_data ) {
            $competition  = $comp_data['competition'];
            $event_models = [];
            foreach ( $comp_data['events'] as $e_data ) {
                $event          = $e_data['event'];
                $teams          = $e_data['teams'];
                $team_rows      = $team_details_presenter->present_team_rows( $teams, $event, null );
                $event_models[] = $club_presenter->present_club_event( $event, $result->club, $team_rows );
            }
            $comp_vm = $event_models ? $club_presenter->present_club_competition( $competition, $result->club, $event_models ) : null;
            if ( $comp_vm ) {
                $competitions[] = $comp_vm;
            }
        }

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $result->template ? 'competitions-' . $result->template : 'competitions',
                vars: [
                    'club'            => $result->club,
                    'competitions'    => $competitions,
                    'user_can_update' => $result->user_can_update,
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [club-invoices template=X]; the invoice is taken from the invoice query var.
     */
    public function show_club_invoices( array|string $atts = [] ): string {
        $user_id     = null;
        $invoice_ref = get_query_var( 'invoice' );
        if ( is_user_logged_in() ) {
            $user    = wp_get_current_user();
            $user_id = (int) ( $user->ID ?? 0 );
        }
        $query = new Get_Club_Invoices_Query(
            club_name: $this->club_name_from_request(),
            invoice_ref: $invoice_ref ? (string) $invoice_ref : null,
            template: $this->template_attribute( $atts ),
            user_id: $user_id,
            is_admin: current_user_can( 'manage_racketmanager' )
        );

        $result = $this->invoices_handler->handle( $query );

        if ( $result->is_error() ) {
            return $this->view_presenter->render( Shortcode_View_Model::error( $result->error_message ?? '' ) );
        }

        $club_presenter    = $this->club_presenter ?? new Club_Presenter();
        $invoice_presenter = $this->invoice_presenter ?? new Invoice_Presenter();

        $invoices_vm = null;
        $invoice_vm  = null;

        if ( 'invoice' === $result->template_ref && $result->invoice ) {
            $return_url = $result->club ? $club_presenter->get_link( $result->club, 'invoices' ) : '';
            $details    = function_exists( '\Racketmanager\show_invoice' ) ? \Racketmanager\show_invoice( $result->invoice->id ) : '';
            $invoice_vm = $invoice_presenter->present_club_invoice_detail(
                invoice: $result->invoice,
                club: $result->club,
                return_url: $return_url,
                details: $details
            );
        } elseif ( null !== $result->invoices_data ) {
            $invoices_vm = $invoice_presenter->present_club_invoices_list(
                invoices: $result->invoices_data,
                currency_code: $result->currency_code,
                date_format: $result->date_format
            );
        }

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $result->template ? $result->template_ref . '-' . $result->template : $result->template_ref,
                vars: [
                    'club'            => $result->club,
                    'user_can_manage' => $result->user_can_manage,
                    'invoices'        => $invoices_vm,
                    'invoice'         => $invoice_vm,
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [club-event template=X]; club and event come from the club_name, event and season query vars.
     */
    public function show_club_event( array|string $atts = [] ): string {
        $template = $this->template_attribute( $atts );

        try {
            $club = $this->club_service->get_club_by_shortcode( $this->club_name_from_request() );
        } catch ( Club_Not_Found_Exception $e ) {
            return $this->view_presenter->render_error( $e->getMessage() );
        }

        $event_name = get_query_var( 'event' );
        if ( ! $event_name ) {
            return $this->view_presenter->render_error( __( 'Event id not supplied', 'racketmanager' ) );
        }
        $event = $this->event_repository->find_by_name( Seo_Slug::decode( (string) $event_name ) );
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ) );
        }

        $season = get_query_var( 'season' );
        $this->event_query_service->set_season( $event, $season ? (string) $season : null );

        $season_name = $season ? (string) $season : ( is_array( $event->current_season ) && isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : null );

        if ( ! $season && ! isset( $event->current_season['name'] ) ) {
            return $this->view_presenter->render_error( __( 'No seasons for event', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $season_name ) ) {
            return $this->view_presenter->render_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $player_stats = $this->event_query_service->get_player_stats(
            $event,
            [
                'season' => $season_name ?? '',
                'club'   => $club->id,
            ]
        );

        $player_links = $this->competition_shortcode_presenter->present_club_event_player_links(
            $event,
            $player_stats ?? [],
            $season_name
        );

        $primary_league = ! empty( $event->is_championship )
            ? $this->event_query_service->get_primary_league( $event )
            : null;

        $vars = [
            'club'           => $club,
            'event'          => $event,
            'player_stats'   => $player_stats,
            'player_links'   => $player_links,
            'seasons'        => $event->get_seasons(),
            'current_season' => $event->current_season ?? [],
            'primary_league' => $primary_league,
        ];

        $post_id = function_exists( 'get_the_ID' ) ? get_the_ID() : '';
        if ( ! is_int( $post_id ) && ! is_string( $post_id ) ) {
            $post_id = '';
        }
        $vars['event_header'] = $this->competition_shortcode_presenter->present_event_header(
            event: $event,
            club: $club,
            curr_season: (string) ( $season_name ?? '' ),
            seasons: (array) $event->get_seasons(),
            header_level: 2,
            post_id: $post_id,
            pagename_query: 'club/event'
        );

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $template ? 'event-' . $template : 'event',
                vars: $vars,
                template_type: 'club'
            )
        );
    }

    /**
     * [club-team template=X]; club, team and event come from the club_name, team and event query vars.
     */
    public function show_club_team( array|string $atts = [] ): string {
        $template = $this->template_attribute( $atts );

        try {
            $club = $this->club_service->get_club_by_shortcode( $this->club_name_from_request() );
        } catch ( Club_Not_Found_Exception $e ) {
            return $this->view_presenter->render_error( $e->getMessage() );
        }

        $team_name = get_query_var( 'team' );
        if ( ! $team_name ) {
            return $this->view_presenter->render_error( __( 'Team not supplied', 'racketmanager' ) );
        }
        $team = $this->team_repository->find_by_id( Seo_Slug::decode( (string) $team_name ) );
        if ( ! $team ) {
            return $this->view_presenter->render_error( __( 'Team not found', 'racketmanager' ) );
        }

        $event_name = get_query_var( 'event' );
        if ( ! $event_name ) {
            return $this->view_presenter->render_error( __( 'Event id not supplied', 'racketmanager' ) );
        }
        $event = $this->event_repository->find_by_name( Seo_Slug::decode( (string) $event_name ) );
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ) );
        }

        $season      = get_query_var( 'season' );
        $this->event_query_service->set_season( $event, $season ? (string) $season : null );
        $season_name = $season ? (string) $season : ( is_array( $event->current_season ) && isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : null );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $season_name ) ) {
            return $this->view_presenter->render_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $team_info = $this->event_query_service->get_team_info( $event, (int) $team->id );
        $team_view = (object) array_merge( (array) $team, (array) $team_info );

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $template ? 'team-' . $template : 'team',
                vars: [
                    'club'            => $club,
                    'event'           => $event,
                    'team'            => $team_view,
                    'user_can_update' => $this->club_service->can_user_update( (int) $club->get_id() ),
                ],
                template_type: 'club'
            )
        );
    }

    /**
     * [team-edit id=team_ID event_id=ID modal=X template=X]
     */
    public function show_team_edit_modal( array|string $atts = [] ): string {
        $args     = shortcode_atts(
            [
                'id'       => 0,
                'modal'    => null,
                'event_id' => null,
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );
        $team_id  = ! empty( $args['id'] ) ? (int) $args['id'] : null;
        $event_id = ! empty( $args['event_id'] ) ? (int) $args['event_id'] : null;
        $template = (string) $args['template'];

        if ( ! $team_id ) {
            return $this->view_presenter->render_error( __( 'Team id not supplied', 'racketmanager' ), 'modal' );
        }
        if ( ! $event_id ) {
            return $this->view_presenter->render_error( __( 'Event id not supplied', 'racketmanager' ), 'modal' );
        }

        $team  = $this->team_repository->find_by_id( $team_id );
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $team ) {
            return $this->view_presenter->render_error( __( 'Team not found', 'racketmanager' ), 'modal' );
        }
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ), 'modal' );
        }

        $season      = get_query_var( 'season' );
        $this->event_query_service->set_season( $event, $season ? (string) $season : null );
        $season_name = $season ? (string) $season : ( is_array( $event->current_season ) && isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : null );
        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $season_name ) ) {
            return $this->view_presenter->render_error( __( 'Event not found for competition', 'racketmanager' ), 'modal' );
        }

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: '' !== $template ? 'team-edit-modal-' . $template : 'team-edit-modal',
                vars: [
                    'team'       => $team,
                    'event'      => $event,
                    'modal'      => $args['modal'],
                    'match_days' => Util_Lookup::get_match_days(),
                    'event_team' => $this->event_query_service->get_team_info( $event, $team_id ),
                ],
                template_type: 'club'
            )
        );
    }

    private function template_attribute( array|string $atts ): string {
        $args = shortcode_atts( [ 'template' => '' ], is_array( $atts ) ? $atts : [] );

        return (string) $args['template'];
    }

    private function club_name_from_request(): string {
        return Seo_Slug::decode( (string) get_query_var( 'club_name' ) );
    }
}
