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
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Util\Util_Lookup;
use function current_user_can;
use function get_query_var;
use function is_user_logged_in;
use function Racketmanager\get_event;
use function Racketmanager\get_team;
use function Racketmanager\un_seo_url;
use function shortcode_atts;
use function wp_get_current_user;
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
        private readonly Competition_Shortcode_Presenter $competition_shortcode_presenter
    ) {}

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

        return $this->club_shortcode_presenter->render( $this->get_club_handler->handle( $query ) );
    }

    /**
     * [club-players template=X]; the player is taken from the player_id query var.
     */
    public function show_club_players( array|string $atts = [] ): string {
        $player_name = get_query_var( 'player_id' );
        $referrer    = function_exists( 'wp_get_referer' ) ? wp_get_referer() : false;
        $query       = new Get_Club_Players_Query(
            club_name: $this->club_name_from_request(),
            player_name: $player_name ? un_seo_url( (string) $player_name ) : null,
            template: $this->template_attribute( $atts ),
            referrer: $referrer ?: null
        );

        return $this->view_presenter->render( $this->players_handler->handle( $query ) );
    }

    /**
     * [club-roles template=X]
     */
    public function show_club_roles( array|string $atts = [] ): string {
        $query = new Get_Club_Roles_Query(
            club_name: $this->club_name_from_request(),
            template: $this->template_attribute( $atts )
        );

        return $this->view_presenter->render( $this->roles_handler->handle( $query ) );
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

        return $this->view_presenter->render( $this->role_modal_handler->handle( $query ) );
    }

    /**
     * [club-competitions template=X]; the competition is taken from the competition_name query var.
     */
    public function show_club_competitions( array|string $atts = [] ): string {
        $competition_name = get_query_var( 'competition_name' );
        $query            = new Get_Club_Competitions_Query(
            club_name: $this->club_name_from_request(),
            competition_name: $competition_name ? un_seo_url( (string) $competition_name ) : null,
            template: $this->template_attribute( $atts )
        );

        return $this->view_presenter->render( $this->competitions_handler->handle( $query ) );
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

        return $this->view_presenter->render( $this->invoices_handler->handle( $query ) );
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
        $event = get_event( un_seo_url( (string) $event_name ), 'name' );
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ) );
        }

        $season = get_query_var( 'season' );
        if ( ! $season && ! isset( $event->current_season['name'] ) ) {
            return $this->view_presenter->render_error( __( 'No seasons for event', 'racketmanager' ) );
        }

        $season_name = $season ? (string) $season : ( $event->current_season['name'] ?? null );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $season_name ) ) {
            return $this->view_presenter->render_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $player_stats = $event->get_player_stats(
            [
                'season' => $season_name ?? '',
                'club'   => $club->id,
            ]
        );

        $player_links = $this->competition_shortcode_presenter->present_club_event_player_links(
            $event,
            $player_stats ?? [],
            $event->current_season['name'] ?? ( $season_name ?? null )
        );

        $primary_league = ! empty( $event->is_championship ) && method_exists( $event, 'get_primary_league' )
            ? $event->get_primary_league()
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
        $team = get_team( un_seo_url( (string) $team_name ) );
        if ( ! $team ) {
            return $this->view_presenter->render_error( __( 'Team not found', 'racketmanager' ) );
        }

        $event_name = get_query_var( 'event' );
        if ( ! $event_name ) {
            return $this->view_presenter->render_error( __( 'Event id not supplied', 'racketmanager' ) );
        }
        $event = get_event( un_seo_url( (string) $event_name ), 'name' );
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ) );
        }

        $season      = get_query_var( 'season' );
        $season_name = $season ? (string) $season : ( $event->current_season['name'] ?? null );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $season_name ) ) {
            return $this->view_presenter->render_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $team_view = (object) array_merge( (array) $team, (array) $event->get_team_info( $team->id ) );

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

        $team  = get_team( $team_id );
        $event = get_event( $event_id );
        if ( ! $team ) {
            return $this->view_presenter->render_error( __( 'Team not found', 'racketmanager' ), 'modal' );
        }
        if ( ! $event ) {
            return $this->view_presenter->render_error( __( 'Event not found', 'racketmanager' ), 'modal' );
        }

        $season_name = $event->current_season['name'] ?? null;
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
                    'event_team' => $event->get_team_info( $team_id ),
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
        return un_seo_url( (string) get_query_var( 'club_name' ) );
    }
}
