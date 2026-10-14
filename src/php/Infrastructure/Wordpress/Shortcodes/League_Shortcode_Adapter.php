<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Query;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Query;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Query;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Query;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Presenters\League_Standings_Presenter;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;
use function is_numeric;
use function is_object;
use function method_exists;
use function str_replace;

/**
 * Driving adapter for League shortcodes.
 */
class League_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager,
        private readonly Get_League_Standings_Handler $get_league_standings_handler,
        private readonly League_Standings_Presenter $league_standings_presenter,
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Get_League_Championship_Handler $championship_handler,
        private readonly Get_League_Archive_Handler $archive_handler,
        private readonly Get_League_Crosstable_Handler $crosstable_handler,
        private readonly Get_League_Matches_Handler $matches_handler,
        private readonly Get_League_Teams_Handler $teams_handler,
        private readonly Get_League_Players_Handler $players_handler,
        private readonly Get_League_Season_Dropdown_Handler $season_dropdown_handler,
        private readonly Get_League_Match_Dropdown_Handler $match_dropdown_handler,
        private readonly Get_League_Last_5_Handler $last_5_handler,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null
    ) {}

    public function show_championship( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Championship_Query::from_atts( $atts, $league_id );
        $vm         = $this->championship_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_archive( array|string $atts = [] ): string {
        $query_vars  = $this->extract_query_vars();
        $league_name = $this->resolve_league_name( $atts, $query_vars );
        $query       = Get_League_Archive_Query::from_atts( $atts, $query_vars, $league_name );
        $vm          = $this->archive_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_standings( array|string $atts = [], bool $widget = false ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Standings_Query::from_atts( $atts, $widget, $league_id );
        $vm         = $this->get_league_standings_handler->handle( $query );
        if ( ! $vm ) {
            return '';
        }

        return $this->league_standings_presenter->render( $vm );
    }

    public function show_crosstable( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Crosstable_Query::from_atts( $atts, $league_id );
        $vm         = $this->crosstable_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Matches_Query::from_atts( $atts, $league_id );
        $vm         = $this->matches_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_teams( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Teams_Query::from_atts( $atts, $query_vars, $league_id );
        $vm         = $this->teams_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_league_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Players_Query::from_atts( $atts, $query_vars, $league_id );
        $vm         = $this->players_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_season_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Season_Dropdown_Query::from_atts( $atts, $league_id );
        $vm         = $this->season_dropdown_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_match_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Match_Dropdown_Query::from_atts( $atts, $league_id );
        $vm         = $this->match_dropdown_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_last_5( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Last_5_Query::from_atts( $atts, $league_id );
        $vm         = $this->last_5_handler->handle( $query );

        return $this->view_presenter->render( $vm );
    }

    public function show_match( array|string $atts = [] ): string {
        if ( $this->racketmanager && $this->racketmanager->container->has( 'fixture_shortcode_adapter' ) ) {
            $adapter = $this->racketmanager->container->get( 'fixture_shortcode_adapter' );
            if ( is_object( $adapter ) && method_exists( $adapter, 'handle' ) ) {
                return $adapter->handle( $atts );
            }
        }

        return $this->render_alert( __( 'Match not found', 'racketmanager' ) );
    }

    private function extract_query_vars(): array {
        global $wp;

        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
            return $wp->query_vars;
        }

        return [];
    }

    private function resolve_league_id( array|string $atts, array $query_vars = [] ): ?int {
        $atts_array = is_array( $atts ) ? $atts : [];
        if ( isset( $atts_array['league_id'] ) && is_numeric( $atts_array['league_id'] ) && (int) $atts_array['league_id'] > 0 ) {
            return (int) $atts_array['league_id'];
        }
        if ( isset( $atts_array['id'] ) && is_numeric( $atts_array['id'] ) && (int) $atts_array['id'] > 0 ) {
            return (int) $atts_array['id'];
        }
        if ( isset( $query_vars['league_id'] ) && is_numeric( $query_vars['league_id'] ) && (int) $query_vars['league_id'] > 0 ) {
            return (int) $query_vars['league_id'];
        }
        if ( isset( $GLOBALS['league'] ) && is_object( $GLOBALS['league'] ) && ! empty( $GLOBALS['league']->id ) && is_numeric( $GLOBALS['league']->id ) ) {
            return (int) $GLOBALS['league']->id;
        }

        return null;
    }

    private function resolve_league_name( array|string $atts, array $query_vars = [] ): ?string {
        $atts_array = is_array( $atts ) ? $atts : [];
        if ( ! empty( $atts_array['league_name'] ) ) {
            return (string) $atts_array['league_name'];
        }
        if ( ! empty( $atts_array['league'] ) ) {
            return (string) $atts_array['league'];
        }
        if ( ! empty( $query_vars['league_name'] ) ) {
            return str_replace( '-', ' ', (string) $query_vars['league_name'] );
        }
        if ( ! empty( $query_vars['league'] ) ) {
            return str_replace( '-', ' ', (string) $query_vars['league'] );
        }
        if ( ! empty( $atts_array['league_id'] ) ) {
            return (string) $atts_array['league_id'];
        }
        if ( ! empty( $atts_array['id'] ) ) {
            return (string) $atts_array['id'];
        }
        if ( ! empty( $query_vars['league_id'] ) ) {
            return (string) $query_vars['league_id'];
        }
        if ( isset( $GLOBALS['league'] ) && is_object( $GLOBALS['league'] ) ) {
            return ! empty( $GLOBALS['league']->title ) ? (string) $GLOBALS['league']->title : ( ! empty( $GLOBALS['league']->id ) ? (string) $GLOBALS['league']->id : null );
        }

        return null;
    }

    private function render_alert( string $msg, ?string $variant = null ): string {
        if ( $this->template_renderer ) {
            return $this->template_renderer->render_alert( $msg, $variant );
        }

        return $msg;
    }
}
