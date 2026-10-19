<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use BackedEnum;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Query;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Query;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function current_user_can;
use function get_current_user_id;
use function get_query_var;
use function is_array;
use function is_object;
use function is_user_logged_in;
use function wp_get_current_user;
use function __;

/**
 * Driving adapter for Competition shortcodes.
 */
class Competition_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    private readonly ?Shortcode_View_Presenter $view_presenter;
    private readonly ?Get_Competition_Entry_Handler $entry_handler;

    public function __construct(
        private readonly ?Competition_Service $competition_service = null,
        private readonly ?Club_Service $club_service = null,
        private readonly ?Team_Service $team_service = null,
        private readonly ?Player_Service $player_service = null,
        private readonly ?Tournament_Service $tournament_service = null,
        private readonly ?Finance_Service $finance_service = null,
        private readonly ?Registration_Service $registration_service = null,
        private readonly ?Competition_Entry_Service $competition_entry_service = null,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Get_Competition_Handler $get_competition_handler = null,
        private readonly ?Competition_Shortcode_Presenter $competition_shortcode_presenter = null,
        private readonly ?Get_Competition_Overview_Handler $overview_handler = null,
        private readonly ?Get_Competition_Events_Handler $events_handler = null,
        private readonly ?Get_Competition_Teams_Handler $teams_handler = null,
        private readonly ?Get_Competition_Clubs_Handler $clubs_handler = null,
        private readonly ?Get_Competition_Players_Handler $players_handler = null,
        private readonly ?Get_Competition_Winners_Handler $winners_handler = null,
        ?Shortcode_View_Presenter $view_presenter = null,
        private readonly ?RacketManager $racketmanager = null,
        private readonly ?Get_Competition_Dropdown_Handler $dropdown_handler = null,
        private readonly ?Get_Competitions_Handler $competitions_handler = null,
        private readonly ?Get_Competition_Payment_Handler $payment_handler = null,
        private readonly ?Club_Entry_Selection_Service $club_entry_selection = null,
        ?Get_Competition_Entry_Handler $entry_handler = null,
        private readonly ?Tournament_Presenter $tournament_presenter = null
    ) {
        $this->view_presenter = $view_presenter ?? ( $template_renderer ? new Shortcode_View_Presenter( $template_renderer ) : null );
        $selection_service = $club_entry_selection ?? ( ( $club_service && $player_service ) ? new Club_Entry_Selection_Service( $club_service, $player_service ) : null );
        $entry_services_available = $selection_service && $competition_service && $tournament_service && $player_service && $club_service && $team_service && $registration_service && $competition_entry_service && $finance_service && $competition_shortcode_presenter && $this->tournament_presenter;
        $this->entry_handler = $entry_handler ?? ( $entry_services_available ? new Get_Competition_Entry_Handler(
            club_entry_selection: $selection_service,
            competition_service: $competition_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $registration_service,
            competition_entry_service: $competition_entry_service,
            finance_service: $finance_service,
            competition_presenter: $competition_shortcode_presenter,
            tournament_presenter: $this->tournament_presenter
        ) : null );
    }

    /**
     * Show competitions shortcode: [competitions type=X age_group=X]
     */
    public function show_competitions( array|string $atts = [] ): string {
        if ( ! $this->competitions_handler || ! $this->view_presenter ) {
            return $this->render_alert( __( 'Competition type not set', 'racketmanager' ) );
        }
        global $wp;
        $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $query = Get_Competitions_Query::from_atts( $atts, $query_vars, $_GET ?? [], $user_id );

        return $this->view_presenter->render( $this->competitions_handler->handle( $query ) );
    }

    /**
     * Show competition shortcode: [competition competition=X season=X template=X]
     */
    public function show_competition( array|string $atts = [] ): string {
        if ( $this->get_competition_handler && $this->competition_shortcode_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->get_competition_handler->handle( $query );

            return $this->competition_shortcode_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition overview: [competition-overview id=ID season=X template=X]
     */
    public function show_competition_overview( array|string $atts = [] ): string {
        if ( $this->overview_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Overview_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->overview_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition events: [competition-events id=ID season=X template=X]
     */
    public function show_competition_events( array|string $atts = [] ): string {
        if ( $this->events_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Events_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->events_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition teams: [competition-teams id=ID season=X template=X]
     */
    public function show_competition_teams( array|string $atts = [] ): string {
        if ( $this->teams_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Teams_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->teams_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition clubs: [competition-clubs id=ID season=X clubs=X template=X]
     */
    public function show_competition_clubs( array|string $atts = [] ): string {
        if ( $this->clubs_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Clubs_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->clubs_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition players: [competition-players id=ID season=X players=X template=X]
     */
    public function show_competition_players( array|string $atts = [] ): string {
        if ( $this->players_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Players_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->players_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition winners: [competition-winners id=ID season=X template=X]
     */
    public function show_competition_winners( array|string $atts = [] ): string {
        if ( $this->winners_handler && $this->view_presenter ) {
            global $wp;
            $query_vars = ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : [];
            $get        = $_GET ?? [];
            $query      = Get_Competition_Winners_Query::from_atts( is_array( $atts ) ? $atts : [], $query_vars, $get );
            $vm         = $this->winners_handler->handle( $query );

            return $this->view_presenter->render( $vm );
        }

        return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
    }

    /**
     * Show competition entry: [competition-entry id=ID template=X]
     */
    public function show_competition_entry( array|string $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return $this->render_alert( __( 'You need to be logged in to enter', 'racketmanager' ) );
        }

        if ( ! $this->entry_handler || ! $this->view_presenter ) {
            return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
        }

        $query_vars = [
            'competition_name' => get_query_var( 'competition_name' ),
            'competition_type' => get_query_var( 'competition_type' ),
            'tournament'       => get_query_var( 'tournament' ),
            'season'           => get_query_var( 'season' ),
            'club_name'        => get_query_var( 'club_name' ),
            'player_id'        => get_query_var( 'player_id' ),
        ];
        $query = Get_Competition_Entry_Query::from_atts(
            $atts,
            $query_vars,
            (int) ( wp_get_current_user()->ID ?? 0 ),
            current_user_can( 'manage_racketmanager' )
        );
        $query = $query->with_entry_level( $this->get_entry_level( $query ) );

        return $this->view_presenter->render( $this->entry_handler->handle( $query ) );
    }

    private function get_entry_level( Get_Competition_Entry_Query $query ): ?string {
        if ( ! $query->competition_name || $query->tournament_name || ! $this->competition_service ) {
            return null;
        }

        try {
            $competition = $this->competition_service->get_by_id( $query->competition_name );
        } catch ( Competition_Not_Found_Exception ) {
            return null;
        }

        $type = $competition->get_type();
        if ( $type instanceof BackedEnum ) {
            $type = $type->value;
        }
        if ( ! is_string( $type ) || '' === $type ) {
            return null;
        }

        $competition_options = $this->racketmanager?->get_options( $type );
        $entry_level        = is_array( $competition_options ) ? ( $competition_options['entry_level'] ?? null ) : null;

        return is_string( $entry_level ) ? $entry_level : null;
    }

    /**
     * Show competition payment page: [competition-entry-payment template=X]
     */
    public function show_competition_entry_payment( array|string $atts = [] ): string {
        return $this->show_payment( $atts, false );
    }

    /**
     * Show competition payment complete: [competition-entry-payment-complete]
     */
    public function show_competition_entry_payment_complete( array|string $atts = [] ): string {
        return $this->show_payment( $atts, true );
    }

    private function show_payment( array|string $atts, bool $complete ): string {
        if ( ! $this->payment_handler || ! $this->view_presenter ) {
            return $this->render_alert( __( 'Tournament not found', 'racketmanager' ) );
        }
        $query_vars = [
            'competition_type' => get_query_var( 'competition_type' ),
            'tournament'       => get_query_var( 'tournament' ),
        ];
        $billing_options = $this->racketmanager?->get_options( 'billing' );
        $query = Get_Competition_Payment_Query::from_atts(
            $atts, $query_vars, (int) ( wp_get_current_user()->ID ?? 0 ),
            is_array( $billing_options ) ? $billing_options : [], $complete
        );

        return $this->view_presenter->render( $this->payment_handler->handle( $query ) );
    }

    /**
     * Show event dropdown: [event-dropdown id=ID template=X]
     */
    public function show_dropdown( array|string $atts = [] ): string {
        if ( ! $this->dropdown_handler || ! $this->view_presenter ) {
            return $this->render_alert( __( 'Competition not found', 'racketmanager' ) );
        }

        return $this->view_presenter->render( $this->dropdown_handler->handle( Get_Competition_Dropdown_Query::from_atts( $atts ) ) );
    }

    /**
     * Check if club selection is available for competition entry.
     */
    public function club_selection_available( object $competition, false|int $club_id = false ): object|int|bool|array {
        if ( ! $this->club_entry_selection ) {
            return false;
        }

        $type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? '' );
        if ( $type instanceof BackedEnum ) {
            $type = $type->value;
        }
        $competition_options = $this->racketmanager?->get_options( (string) $type );
        $entry_level = is_array( $competition_options ) ? ( $competition_options['entry_level'] ?? null ) : null;

        return $this->club_entry_selection->available_clubs(
            current_user_can( 'manage_racketmanager' ),
            (int) ( wp_get_current_user()->ID ?? 0 ),
            $club_id ?: null,
            is_string( $entry_level ) ? $entry_level : null
        );
    }

    private function render_alert( string $msg, ?string $variant = null ): string {
        if ( $this->template_renderer ) {
            return $this->template_renderer->render_alert( $msg, $variant );
        }

        return $msg;
    }
}
