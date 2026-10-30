<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\League\DTOs\League_Archive_Result;
use Racketmanager\Application\League\DTOs\League_Championship_Result;
use Racketmanager\Application\League\DTOs\League_Crosstable_Result;
use Racketmanager\Application\League\DTOs\League_Last_5_Result;
use Racketmanager\Application\League\DTOs\League_Match_Dropdown_Result;
use Racketmanager\Application\League\DTOs\League_Matches_Result;
use Racketmanager\Application\League\DTOs\League_Players_Result;
use Racketmanager\Application\League\DTOs\League_Season_Dropdown_Result;
use Racketmanager\Application\League\DTOs\League_Standings_Result;
use Racketmanager\Application\League\DTOs\League_Teams_Result;
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
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use function __;
use function count;
use function function_exists;
use function is_array;
use function is_iterable;
use function is_numeric;
use function is_object;
use function method_exists;
use function set_query_var;
use function sprintf;
use function str_replace;

/**
 * Driving adapter for League shortcodes.
 */
class League_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    private readonly ?Shortcode_View_Presenter $view_presenter;
    private readonly League_Archive_Presenter $archive_presenter;
    private readonly Tournament_Presenter $tournament_presenter;
    private readonly Team_Details_Presenter $team_details_presenter;
    private readonly Player_Presenter $player_presenter;
    private readonly Fixture_Presenter $fixture_presenter;

    public function __construct(
        private readonly ?RacketManager $racketmanager = null,
        private readonly ?Get_League_Standings_Handler $get_league_standings_handler = null,
        private readonly ?League_Standings_Presenter $league_standings_presenter = null,
        ?Shortcode_View_Presenter $view_presenter = null,
        private readonly ?Get_League_Championship_Handler $championship_handler = null,
        private readonly ?Get_League_Archive_Handler $archive_handler = null,
        private readonly ?Get_League_Crosstable_Handler $crosstable_handler = null,
        private readonly ?Get_League_Matches_Handler $matches_handler = null,
        private readonly ?Get_League_Teams_Handler $teams_handler = null,
        private readonly ?Get_League_Players_Handler $players_handler = null,
        private readonly ?Get_League_Season_Dropdown_Handler $season_dropdown_handler = null,
        private readonly ?Get_League_Match_Dropdown_Handler $match_dropdown_handler = null,
        private readonly ?Get_League_Last_5_Handler $last_5_handler = null,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        ?League_Archive_Presenter $archive_presenter = null,
        ?Tournament_Presenter $tournament_presenter = null,
        ?Team_Details_Presenter $team_details_presenter = null,
        ?Player_Presenter $player_presenter = null,
        ?Fixture_Presenter $fixture_presenter = null,
        private readonly ?Competition_Service $competition_service = null,
        private readonly ?Team_Service $team_service = null,
        private readonly ?Fixture_Link_Service $fixture_link_service = null,
        private readonly ?Fixture_Permission_Service $fixture_permission_service = null
    ) {
        $this->view_presenter         = $view_presenter ?? ( $template_renderer ? new Shortcode_View_Presenter( $template_renderer ) : null );
        $option_gateway               = new Wp_Option_Gateway();
        $date_format                  = (string) $option_gateway->get_option( 'date_format', 'Y-m-d' );
        $this->archive_presenter      = $archive_presenter ?? new League_Archive_Presenter( $option_gateway );
        $this->tournament_presenter   = $tournament_presenter ?? new Tournament_Presenter( $date_format );
        $this->team_details_presenter = $team_details_presenter ?? new Team_Details_Presenter( $option_gateway, new Settings_Service() );
        $this->player_presenter       = $player_presenter ?? new Player_Presenter();
        $this->fixture_presenter      = $fixture_presenter ?? new Fixture_Presenter(
            $fixture_link_service ?? new Fixture_Link_Service(),
            $option_gateway
        );
    }

    public function show_championship( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Championship_Query::from_atts( $atts, $league_id );
        $result     = $this->championship_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_championship( $result ) ) : '';
    }

    public function present_championship( League_Championship_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league        = $result->league;
        $competition   = $league?->event?->competition ?? null;
        $is_tournament = $this->tournament_presenter->is_tournament_competition( $competition );
        $finals        = [];

        foreach ( $result->finals as $f => $data ) {
            $matches = [];
            $i       = 1;

            foreach ( is_iterable( $data->matches ?? null ) ? $data->matches : [] as $fixture ) {
                if ( $is_tournament ) {
                    $matches[ $i ] = $this->tournament_presenter->present_match_card( $fixture, null, null, 'list', false, true );
                    ++$i;
                    continue;
                }

                $match             = Fixture_Presentation_Adapter::from_fixture(
                    $fixture,
                    $this->competition_service ?? $this->resolve_service( 'competition_service' ),
                    $this->team_service ?? $this->resolve_service( 'team_service' ),
                    $this->fixture_link_service,
                    $this->fixture_permission_service
                );
                $home_title        = $match->teams['home']->title ?? '';
                $away_title        = $match->teams['away']->title ?? '';
                $match->title      = sprintf( '%s - %s', $home_title, $away_title );
                $match->home_title = $home_title;
                $match->away_title = $away_title;

                $match->num_rubbers = $result->num_rubbers;
                $match->num_sets    = $result->num_sets;

                if ( isset( $match->num_rubbers ) && $match->num_rubbers > 0 ) {
                    $rubbers = $match->get_rubbers();
                    $r       = 1;
                    if ( is_iterable( $rubbers ) ) {
                        foreach ( $rubbers as $rubber ) {
                            $match->rubbers[ $r ] = $rubber;
                            ++$r;
                        }
                    }
                }
                if ( empty( $match->location ) ) {
                    $match->location = '';
                }
                $matches[ $i ] = $match;
                ++$i;
            }

            if ( count( $matches ) > 0 ) {
                $item          = clone $data;
                $item->matches = $this->tournament_presenter->present_match_list(
                    $matches,
                    $is_tournament,
                    [ 'show_header' => false ]
                );
                $finals[ $f ]  = $item;
            }
        }

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'championship-' . $league->sport ) ) {
            $filename = 'championship-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'championship-' . $result->template : 'championship';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
                'finals' => $finals,
            ],
            false
        );
    }

    public function show_archive( array|string $atts = [] ): string {
        $query_vars  = $this->extract_query_vars();
        $league_name = $this->resolve_league_name( $atts, $query_vars );
        $query       = Get_League_Archive_Query::from_atts( $atts, $query_vars, $league_name, is_array( $_GET ) ? $_GET : [] );
        if ( '-1' === $query->match_day && function_exists( 'set_query_var' ) ) {
            set_query_var( 'match_day', '-1' );
        }
        $result = $this->archive_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_archive( $result ) ) : '';
    }

    public function present_archive( League_Archive_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() || ! $result->league || ! $result->event ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league = $result->league;

        $archive_vm = $this->archive_presenter->present_archive(
            league: $league,
            event: $result->event,
            seasons: $result->seasons,
            leagues: $result->leagues,
            tab: $result->tab,
            post_id: $result->post_id
        );

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'archive-' . $league->sport ) ) {
            $filename = 'archive-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'archive-' . $result->template : 'archive';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'archive_vm'          => $archive_vm,
                'vm'                  => $archive_vm,
                'league'              => $league,
                'seasons'             => $result->seasons,
                'leagues'             => $result->leagues,
                'tab'                 => $result->tab,
                'post_id'             => $archive_vm->post_id,
                'pagename'            => $archive_vm->pagename,
                'form_action'         => $archive_vm->form_action,
                'competition_type'    => $archive_vm->competition_type,
                'league_seo_name'     => $archive_vm->league_seo_name,
                'icon_path'           => $archive_vm->icon_path,
                'icon_url'            => $archive_vm->icon_url,
                'calendar_icon_url'   => $archive_vm->calendar_icon_url,
                'event_name'          => $archive_vm->event_name,
                'event_url'           => $archive_vm->event_url,
                'competition_name'    => $archive_vm->competition_name,
                'competition_url'     => $archive_vm->competition_url,
                'current_season_name' => $archive_vm->current_season_name,
                'date_start_display'  => $archive_vm->date_start_display,
                'date_end_display'    => $archive_vm->date_end_display,
                'has_date_range'      => $archive_vm->has_date_range,
                'season_title'        => $archive_vm->season_title,
                'season_selection'    => $archive_vm->season_selection,
                'season_options'      => $archive_vm->season_options,
                'league_options'      => $archive_vm->league_options,
                'favourite_id'        => $archive_vm->favourite_id,
                'favourite_type'      => $archive_vm->favourite_type,
                'is_championship'     => $archive_vm->is_championship,
                'championship_season' => $archive_vm->championship_season,
                'menu_options'        => $archive_vm->menu_options,
            ],
            false
        );
    }

    public function show_standings( array|string $atts = [], bool $widget = false ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Standings_Query::from_atts( $atts, $widget, $league_id );
        $result     = $this->get_league_standings_handler->handle( $query );
        if ( ! $result ) {
            return '';
        }

        $vm = $this->present_standings( $result );
        if ( ! $vm ) {
            return '';
        }

        return $this->league_standings_presenter->render( $vm );
    }

    public function present_standings( ?League_Standings_Result $result ): ?League_Standings_View_Model {
        if ( ! $result || $result->is_error() ) {
            return null;
        }

        return new League_Standings_View_Model(
            template: $result->template,
            league: $result->league,
            teams: $result->teams,
            widget: $result->widget,
            season: $result->season,
            group: $result->group
        );
    }

    public function show_crosstable( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Crosstable_Query::from_atts( $atts, $league_id );
        $result     = $this->crosstable_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_crosstable( $result ) ) : '';
    }

    public function present_crosstable( League_Crosstable_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league = $result->league;

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'crosstable-' . $league->sport, 'league' ) ) {
            $filename = 'crosstable-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'crosstable-' . $result->template : 'crosstable';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'             => $league,
                'teams'              => $result->teams,
                'crosstable_columns' => $result->columns,
                'crosstable_cells'   => $result->cells,
                'mode'               => $result->mode,
                'season'             => $result->season,
            ],
            'league'
        );
    }

    public function show_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Matches_Query::from_atts( $atts, $league_id );
        $result     = $this->matches_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_matches( $result ) ) : '';
    }

    public function present_matches( League_Matches_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league      = $result->league;
        $competition = $league?->event?->competition ?? null;

        if ( ! $result->is_team_entry ) {
            // League pages have no Tournament context: cards link to the league match page, like the legacy template.
            $league_matches = $this->tournament_presenter->present_match_cards(
                $result->raw_matches,
                null,
                null,
                '',
                false,
                $this->tournament_presenter->is_tournament_competition( $competition )
            );
        } elseif ( $result->is_grouped ) {
            $league_matches = [];
            foreach ( $result->grouped_matches as $key => $day_matches ) {
                $presented              = $this->tournament_presenter->present_match_list( $day_matches, options: [ 'show_header' => false, 'matches_key' => 'match_day' ] );
                $league_matches[ $key ] = new Team_Matches_Group_View_Model(
                    key: (string) $key,
                    header: sprintf( __( 'Match Day %d', 'racketmanager' ), (int) $key ),
                    matches: $presented
                );
            }
        } else {
            $league_matches = $this->tournament_presenter->present_match_list( $result->raw_matches, options: [ 'show_header' => false ] );
        }

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'matches-' . $league->sport ) ) {
            $filename = 'matches-' . $league->sport;
        } elseif ( ! empty( $league->sport ) && $this->template_exists( 'matches-' . $result->template . '-' . $league->sport ) ) {
            $filename = 'matches-' . $result->template . '-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'matches-' . $result->template : 'matches';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'      => $league,
                'matches'     => $league_matches,
                'teams'       => $result->teams,
                'season'      => $result->season,
                'post_id'     => $result->post_id,
                'form_action' => $result->form_action,
            ]
        );
    }

    public function show_teams( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Teams_Query::from_atts( $atts, $query_vars, $league_id );
        $result     = $this->teams_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_teams( $result ) ) : '';
    }

    public function present_teams( League_Teams_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league = $result->league;

        if ( $result->is_team_details && $result->team ) {
            $team        = $result->team;
            $competition = $league?->event?->competition ?? null;
            // Same condition as templates/team-details.php, which renders match cards for player-entry competitions.
            if ( $competition?->is_player_entry ?? false ) {
                // League pages have no Tournament context: cards link to the league match page, like the legacy template.
                $team->matches = $this->tournament_presenter->present_match_cards(
                    $result->raw_matches,
                    null,
                    null,
                    '',
                    false,
                    $this->tournament_presenter->is_tournament_competition( $competition )
                );
            } else {
                $team->matches = $this->tournament_presenter->present_match_list(
                    $result->raw_matches,
                    null,
                    [
                        'current_team' => (int) $team->id,
                        'show_header'  => false,
                    ]
                );
            }

            $vm = $this->team_details_presenter->present( $league, $result->team_details, $result->user_can_edit_team );

            return Shortcode_View_Model::for_template(
                'team-details',
                [
                    'league'       => $league,
                    'teams'        => [],
                    'object'       => $league,
                    'team_details' => $result->team_details,
                    'vm'           => $vm,
                ],
                false
            );
        }

        $presented_teams = $this->team_details_presenter->present_team_rows(
            $result->league_teams,
            $league,
            $result->item_link
        );

        return Shortcode_View_Model::for_template(
            'teams-list',
            [
                'league'       => $league,
                'team_rows'    => $presented_teams,
                'object'       => $league,
                'team_details' => [],
            ],
            false
        );
    }

    public function show_league_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Players_Query::from_atts( $atts, $query_vars, $league_id );
        $result     = $this->players_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_players( $result ) ) : '';
    }

    public function present_players( League_Players_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league       = $result->league;
        $player_vm    = null;
        $player_links = [];

        if ( $result->player ) {
            $player_vm = $this->player_presenter->present_player( $result->player );
        }

        if ( ! empty( $result->raw_players ) ) {
            $player_links = $this->player_presenter->present_league_player_links( $league, $result->raw_players );
        }

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'players-' . $league->sport, 'league' ) ) {
            $filename = 'players-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'players-' . $result->template : 'players';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'       => $league,
                'player_vm'    => $player_vm,
                'player_links' => $player_links,
            ],
            'league'
        );
    }

    public function show_season_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Season_Dropdown_Query::from_atts( $atts, $league_id );
        $result     = $this->season_dropdown_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_season_dropdown( $result ) ) : '';
    }

    public function present_season_dropdown( League_Season_Dropdown_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league = $result->league;

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'season-dropdown-' . $league->sport, 'league' ) ) {
            $filename = 'season-dropdown-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'season-dropdown-' . $result->template : 'season-dropdown';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
                'season' => $result->season,
            ],
            'league'
        );
    }

    public function show_match_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Match_Dropdown_Query::from_atts( $atts, $league_id );
        $result     = $this->match_dropdown_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_match_dropdown( $result ) ) : '';
    }

    public function present_match_dropdown( League_Match_Dropdown_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league = $result->league;

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'match-dropdown-' . $league->sport, 'league' ) ) {
            $filename = 'match-dropdown-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'match-dropdown-' . $result->template : 'match-dropdown';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches'  => $result->matches,
                'match_id' => $result->match_id,
            ],
            'league'
        );
    }

    public function show_last_5( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Last_5_Query::from_atts( $atts, $league_id );
        $result     = $this->last_5_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_last_5( $result ) ) : '';
    }

    public function present_last_5( League_Last_5_Result $result ): Shortcode_View_Model {
        if ( $result->is_error() ) {
            return Shortcode_View_Model::for_error( $result->error_message ?? __( 'League not found', 'racketmanager' ) );
        }

        $league         = $result->league;
        $team_titles    = $result->team_titles;
        $match_tooltips = [];

        foreach ( $result->matches as $match ) {
            $match_id = is_object( $match ) ? ( $match->id ?? null ) : ( $match['id'] ?? null );
            if ( null === $match_id ) {
                continue;
            }
            $home_id = is_object( $match ) ? ( $match->home_team ?? null ) : ( $match['home_team'] ?? null );
            $away_id = is_object( $match ) ? ( $match->away_team ?? null ) : ( $match['away_team'] ?? null );
            $match_tooltips[ $match_id ] = $this->fixture_presenter->present_last_5_tooltip(
                $match,
                $team_titles[ (string) $home_id ] ?? null,
                $team_titles[ (string) $away_id ] ?? null
            );
        }

        if ( empty( $result->template ) && ! empty( $league->sport ) && $this->template_exists( 'last-5-' . $league->sport, 'league' ) ) {
            $filename = 'last-5-' . $league->sport;
        } else {
            $filename = ( ! empty( $result->template ) ) ? 'last-5-' . $result->template : 'last-5';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches'        => $result->matches,
                'team_id'        => $result->team_id,
                'match_tooltips' => $match_tooltips,
            ],
            'league'
        );
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

    private function template_exists( string $template, false|string $template_type = false ): bool {
        return null !== $this->template_renderer && $this->template_renderer->template_exists( $template, $template_type );
    }

    private function resolve_service( string $id ): mixed {
        return $this->racketmanager?->container->get( $id );
    }

    private function render_alert( string $message ): string {
        $vm = Shortcode_View_Model::for_error( $message );

        return $this->view_presenter->render( $vm );
    }
}
