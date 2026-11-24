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
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Throwable;
use function __;
use function is_array;
use function is_iterable;
use function is_numeric;
use function is_object;
use function method_exists;
use function sprintf;
use function str_replace;

/**
 * Driving adapter for League shortcodes.
 */
readonly class League_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    private ?Shortcode_View_Presenter $view_presenter;

    /**
     * Initialises the shortcode controller with presenters, handlers, services, and optional view rendering dependencies.
     *
     * @param League_Archive_Presenter                $archive_presenter Presenter for league archive output.
     * @param Tournament_Presenter                    $tournament_presenter Presenter for tournament output.
     * @param Team_Details_Presenter                  $team_details_presenter Presenter for team details output.
     * @param Player_Presenter                        $player_presenter Presenter for player output.
     * @param Fixture_Presenter                       $fixture_presenter Presenter for fixture output.
     * @param RacketManager|null                      $racketmanager Optional RacketManager instance.
     * @param Get_League_Standings_Handler|null       $get_league_standings_handler Optional handler for league standings requests.
     * @param League_Standings_Presenter|null         $league_standings_presenter Optional presenter for league standings output.
     * @param Shortcode_View_Presenter|null           $view_presenter Optional presenter for shortcode views.
     * @param Get_League_Championship_Handler|null    $championship_handler Optional handler for league championship requests.
     * @param Get_League_Archive_Handler|null         $archive_handler Optional handler for league archive requests.
     * @param Get_League_Crosstable_Handler|null      $crosstable_handler Optional handler for league crosstable requests.
     * @param Get_League_Matches_Handler|null         $matches_handler Optional handler for league matches requests.
     * @param Get_League_Teams_Handler|null           $teams_handler Optional handler for league teams requests.
     * @param Get_League_Players_Handler|null         $players_handler Optional handler for league players requests.
     * @param Get_League_Season_Dropdown_Handler|null $season_dropdown_handler Optional handler for league season dropdown requests.
     * @param Get_League_Match_Dropdown_Handler|null  $match_dropdown_handler Optional handler for league match dropdown requests.
     * @param Get_League_Last_5_Handler|null          $last_5_handler Optional handler for last-five-results requests.
     * @param Shortcode_Template_Renderer|null        $template_renderer Optional renderer used to create a shortcode view presenter when none is provided.
     * @param Competition_Service|null                $competition_service Optional competition service.
     * @param Team_Service|null                       $team_service Optional team service.
     * @param Fixture_Link_Service|null               $fixture_link_service Optional fixture link service.
     * @param Fixture_Permission_Service|null         $fixture_permission_service Optional fixture permission service.
     * @param Query_Vars_Interface|null               $query_vars Optional query variables provider.
     *
     * @return void
     */
    public function __construct(
        private League_Archive_Presenter $archive_presenter,
        private Tournament_Presenter $tournament_presenter,
        private Team_Details_Presenter $team_details_presenter,
        private Player_Presenter $player_presenter,
        private Fixture_Presenter $fixture_presenter,
        private ?RacketManager $racketmanager = null,
        private ?Get_League_Standings_Handler $get_league_standings_handler = null,
        private ?League_Standings_Presenter $league_standings_presenter = null,
        ?Shortcode_View_Presenter $view_presenter = null,
        private ?Get_League_Championship_Handler $championship_handler = null,
        private ?Get_League_Archive_Handler $archive_handler = null,
        private ?Get_League_Crosstable_Handler $crosstable_handler = null,
        private ?Get_League_Matches_Handler $matches_handler = null,
        private ?Get_League_Teams_Handler $teams_handler = null,
        private ?Get_League_Players_Handler $players_handler = null,
        private ?Get_League_Season_Dropdown_Handler $season_dropdown_handler = null,
        private ?Get_League_Match_Dropdown_Handler $match_dropdown_handler = null,
        private ?Get_League_Last_5_Handler $last_5_handler = null,
        private ?Shortcode_Template_Renderer $template_renderer = null,
        private ?Competition_Service $competition_service = null,
        private ?Team_Service $team_service = null,
        private ?Fixture_Link_Service $fixture_link_service = null,
        private ?Fixture_Permission_Service $fixture_permission_service = null,
        private ?Query_Vars_Interface $query_vars = null
    ) {
        $this->view_presenter = $view_presenter ?? ( $template_renderer ? new Shortcode_View_Presenter( $template_renderer ) : null );
    }

    /**
     * Renders the league championship view for the provided shortcode attributes.
     *
     * @param array|string $atts Shortcode attributes used to resolve the league and build the championship query.
     *
     * @return string Rendered championship output, or an empty string when no view presenter is available.
     */
    public function show_championship( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Championship_Query::from_atts( $atts, $league_id );
        $result     = $this->championship_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_championship( $result ) ) : '';
    }

    /**
     * Presents championship data as a shortcode view model, including formatted finals and match details.
     *
     * @param League_Championship_Result $result Championship result data containing league, finals, template, scoring, and error state.
     *
     * @return Shortcode_View_Model View model for the championship template, or an error view model when the result contains an error.
     */
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
                    foreach ( $rubbers as $rubber ) {
                        $match->rubbers[ $r ] = $rubber;
                        ++$r;
                    }
                }
                if ( empty( $match->location ) ) {
                    $match->location = '';
                }
                $matches[ $i ] = $match;
                ++$i;
            }

            if ( ! empty( $matches ) ) {
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

    /**
     * Displays the league archive for the provided shortcode attributes and current query variables.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to build the archive query.
     *
     * @return string Rendered league archive output, or an empty string when no view presenter is available.
     */
    public function show_archive( array|string $atts = [] ): string {
        $query_vars  = $this->extract_query_vars();
        $league_name = $this->resolve_league_name( $atts, $query_vars );
        $query       = Get_League_Archive_Query::from_atts( $atts, $query_vars, $league_name, is_array( $_GET ) ? $_GET : [] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '-1' === $query->match_day ) {
            $this->query_vars?->set( 'match_day', '-1' );
        }
        $result = $this->archive_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_archive( $result ) ) : '';
    }

    /**
     * Presents a league archive result as a shortcode view model, returning an error view when the archive data is invalid.
     *
     * @param League_Archive_Result $result Archive result containing league, event, season, template, and view state data.
     *
     * @return Shortcode_View_Model View model for the selected archive template or an error view model.
     */
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
            post_id: $result->post_id,
            view_state: $result->view_state
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
                'view_state'          => $result->view_state,
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

    /**
     * Renders the league standings output for the provided shortcode attributes.
     *
     * Resolves the league context, builds and handles a standings query, converts the result into a view model,
     * and returns the rendered standings markup. Returns an empty string when standings data or presentation data
     * cannot be produced.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to build the standings query.
     * @param bool         $widget Whether the standings are being rendered in a widget context.
     *
     * @return string Rendered league standings output, or an empty string when no standings can be displayed.
     * @throws Throwable When standings data or presentation data cannot be produced.
     */
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

    /**
     * Converts a league standings result into a view model for presentation.
     *
     * @param League_Standings_Result|null $result League standings result to present, or null when no result is available.
     *
     * @return League_Standings_View_Model|null View model containing standings presentation data, or null when the result is missing or contains an error.
     */
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
            group: $result->group,
            view_state: $result->view_state
        );
    }

    /**
     * Renders the league crosstable shortcode output for the resolved league context.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to build the crosstable query.
     *
     * @return string Rendered crosstable output, or an empty string when no view presenter is available.
     */
    public function show_crosstable( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Crosstable_Query::from_atts( $atts, $league_id );
        $result     = $this->crosstable_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_crosstable( $result ) ) : '';
    }

    /**
     * Builds a shortcode view model for displaying a league crosstable.
     *
     * Selects an appropriate crosstable template based on the result template override or
     * the league sport, and returns an error view model when the crosstable result contains
     * an error.
     *
     * @param League_Crosstable_Result $result Crosstable result containing league data, teams, columns, cells, mode, season, template selection, and error state.
     *
     * @return Shortcode_View_Model View model for the selected crosstable template, or an error view model when the result is invalid.
     */
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

    /**
     * Renders the league matches view for the provided shortcode attributes.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to resolve the league and build the matches query.
     *
     * @return string Rendered matches output, or an empty string when no view presenter is available.
     */
    public function show_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Matches_Query::from_atts( $atts, $league_id );
        $result     = $this->matches_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_matches( $result ) ) : '';
    }

    /**
     * Presents league matches as a shortcode view model, selecting the appropriate match presentation and template.
     *
     * @param League_Matches_Result $result Result containing league match data, grouping state, template options, teams, season, and view context.
     *
     * @return Shortcode_View_Model View model for the selected matches template, or an error view model when the result contains an error.
     */
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
                $presented              = $this->tournament_presenter->present_match_list(
                    $day_matches,
                    options: [
                        'show_header' => false,
                        'matches_key' => 'match_day',
                    ]
                );
                $league_matches[ $key ] = new Team_Matches_Group_View_Model(
                    key: (string) $key,
                    // Translators: %d is the match day number.
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
                'view_state'  => $result->view_state,
            ]
        );
    }

    /**
     * Renders the teams view for a resolved league using shortcode attributes and query variables.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to build the league teams query.
     *
     * @return string Rendered teams output, or an empty string when no view presenter is available.
     */
    public function show_teams( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Teams_Query::from_atts( $atts, $query_vars, $league_id );
        $result     = $this->teams_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_teams( $result ) ) : '';
    }

    /**
     * Presents league team data as a shortcode view model.
     *
     * Returns an error view model when the supplied result contains an error. When
     * team details are requested, prepares the team's presented matches and returns
     * the team details template view model. Otherwise, prepares the league team rows
     * and returns the teams list template view model.
     *
     * @param League_Teams_Result $result League teams result containing league, team, match, and view state data.
     *
     * @return Shortcode_View_Model View model for the teams list, team details, or error output.
     */
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
                $presented_matches = $this->tournament_presenter->present_match_cards(
                    $result->raw_matches,
                    null,
                    null,
                    '',
                    false,
                    $this->tournament_presenter->is_tournament_competition( $competition )
                );
            } else {
                $presented_matches = $this->tournament_presenter->present_match_list(
                    $result->raw_matches,
                    null,
                    [
                        'current_team' => (int) $team->id,
                        'show_header'  => false,
                    ]
                );
            }

            if ( $team instanceof Team ) {
                $team->set_matches( $presented_matches );
            } else {
                $team->matches = $presented_matches;
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
                'view_state'   => $result->view_state,
            ],
            false
        );
    }

    /**
     * Renders the league players shortcode output.
     *
     * Extracts request query variables, resolves the target league, builds the league
     * players query from shortcode attributes and query state, and renders the
     * presented players view when a view presenter is available.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to resolve league and player query state.
     *
     * @return string Rendered league players output, or an empty string when no view presenter is available.
     */
    public function show_league_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Players_Query::from_atts( $atts, $query_vars, $league_id );
        $result     = $this->players_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_players( $result ) ) : '';
    }

    /**
     * Presents league player data as a shortcode view model.
     *
     * Returns an error view model when the supplied result contains an error. Otherwise,
     * prepares the selected player view model, league player links, and resolves the
     * appropriate players template for the league output.
     *
     * @param League_Players_Result $result League players result containing league, player, raw player, template, and error state data.
     *
     * @return Shortcode_View_Model View model for the league players template or error output.
     */
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

    /**
     * Renders the season dropdown for a league.
     *
     * Extracts request query variables, resolves the target league ID from the
     * supplied attributes and query state, retrieves the season dropdown data, and
     * renders the presented view model when a view presenter is available.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to resolve the league and dropdown query.
     *
     * @return string Rendered season dropdown markup, or an empty string when no view presenter is available.
     */
    public function show_season_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Season_Dropdown_Query::from_atts( $atts, $league_id );
        $result     = $this->season_dropdown_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_season_dropdown( $result ) ) : '';
    }

    /**
     * Presents a league season dropdown as a shortcode view model.
     *
     * Returns an error view model when the supplied result contains an error. Otherwise,
     * selects the most appropriate season dropdown template based on the requested
     * template value or the league sport, then returns the view model with league and
     * season data.
     *
     * @param League_Season_Dropdown_Result $result League season dropdown result containing league, season, template, and error state data.
     *
     * @return Shortcode_View_Model View model for the season dropdown or error output.
     */
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

    /**
     * Renders the league match dropdown shortcode output.
     *
     * Extracts request query variables, resolves the league identifier from shortcode
     * attributes and query data, builds the match dropdown query, and renders the
     * presented dropdown result when a view presenter is available.
     *
     * @param array|string $atts Shortcode attributes or raw attribute string used to resolve the league and dropdown query options.
     *
     * @return string Rendered match dropdown output, or an empty string when no view presenter is available.
     */
    public function show_match_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Match_Dropdown_Query::from_atts( $atts, $league_id );
        $result     = $this->match_dropdown_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_match_dropdown( $result ) ) : '';
    }

    /**
     * Presents league matches as a dropdown shortcode view model.
     *
     * Returns an error view model when the supplied result contains an error. Otherwise,
     * selects the most specific available match dropdown template based on the result
     * template value or league sport, and provides the match list and selected match ID
     * to the league template.
     *
     * @param League_Match_Dropdown_Result $result League match dropdown result containing league, matches, selected match ID, template, and error state data.
     *
     * @return Shortcode_View_Model View model for the match dropdown or error output.
     */
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

    /**
     * Renders the last five league results output.
     *
     * Extracts query variables, resolves the target league, builds the last five
     * query from the supplied shortcode attributes, and returns the rendered view
     * output when a view presenter is available.
     *
     * @param array|string $atts Shortcode attributes or attribute string used to resolve the league and query options.
     *
     * @return string Rendered last five results output, or an empty string when no view presenter is available.
     */
    public function show_last_5( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $league_id  = $this->resolve_league_id( $atts, $query_vars );
        $query      = Get_League_Last_5_Query::from_atts( $atts, $league_id );
        $result     = $this->last_5_handler->handle( $query );

        return $this->view_presenter ? $this->view_presenter->render( $this->present_last_5( $result ) ) : '';
    }

    /**
     * Presents recent league form data as a shortcode view model.
     *
     * Returns an error view model when the supplied result contains an error. Otherwise,
     * builds tooltip data for each available match, selects the appropriate last-five
     * league template, and returns a template view model containing the matches, team
     * context, and match tooltip data.
     *
     * @param League_Last_5_Result $result League last-five result containing league, match, team title, template, and view state data.
     *
     * @return Shortcode_View_Model View model for the last-five league output or error output.
     */
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
            $home_id                     = is_object( $match ) ? ( $match->home_team ?? null ) : ( $match['home_team'] ?? null );
            $away_id                     = is_object( $match ) ? ( $match->away_team ?? null ) : ( $match['away_team'] ?? null );
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

    /**
     * Renders the match shortcode output.
     *
     * Delegates shortcode handling to the fixture shortcode adapter when it is
     * available in the dependency container. Returns a translated alert message when
     * no suitable adapter is available.
     *
     * @param array|string $atts Shortcode attributes supplied as an array or string.
     *
     * @return string Rendered match output or fallback alert message.
     */
    public function show_match( array|string $atts = [] ): string {
        if ( $this->racketmanager && $this->racketmanager->container->has( 'fixture_shortcode_adapter' ) ) {
            $adapter = $this->racketmanager->container->get( 'fixture_shortcode_adapter' );
            if ( method_exists( $adapter, 'handle' ) ) {
                return $adapter->handle( $atts );
            }
        }

        return $this->render_alert( __( 'Match not found', 'racketmanager' ) );
    }

    /**
     * Extracts all available query variables.
     *
     * @return array Query variables, or an empty array when no query variables are available.
     */
    private function extract_query_vars(): array {
        return $this->query_vars?->all() ?? [];
    }

    /**
     * Resolves a league ID from shortcode attributes, query variables, or global league context.
     *
     * Checks supported attribute keys first, then query variables, then the global league object.
     * Only positive numeric values are returned.
     *
     * @param array|string $atts Shortcode attributes that may contain a league ID.
     * @param array        $query_vars Query variables that may contain a league ID.
     *
     * @return int|null Resolved league ID, or null when no valid positive numeric ID is found.
     */
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

    /**
     * Resolves a league identifier or name from shortcode attributes, query variables, or global league context.
     *
     * Checks shortcode attributes first, followed by query variables, then falls back to the global league object.
     * Query variable slugs are converted from hyphen-separated values to space-separated names.
     *
     * @param array|string $atts Shortcode attributes containing possible league name or ID values, or a non-array value to ignore.
     * @param array        $query_vars Query variables containing possible league name or ID values.
     *
     * @return string|null Resolved league name or ID, or null when no league reference is available.
     */
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

    /**
     * Determines whether the specified template exists for the optional template type.
     *
     * Checks the configured template renderer and returns whether it can resolve the
     * requested template. Returns false when no template renderer is available.
     *
     * @param string       $template Template name or path to check.
     * @param false|string $template_type Optional template type or false to use the default lookup.
     *
     * @return bool True if the template exists, false otherwise.
     */
    private function template_exists( string $template, false|string $template_type = false ): bool {
        return null !== $this->template_renderer && $this->template_renderer->template_exists( $template, $template_type );
    }

    /**
     * Resolves a service from the RacketManager dependency container.
     *
     * @param string $id Service identifier to retrieve from the container.
     *
     * @return mixed Resolved service instance, or null when the RacketManager instance is unavailable.
     */
    private function resolve_service( string $id ): mixed {
        return $this->racketmanager?->container->get( $id );
    }

    /**
     * Renders an error alert for the supplied message.
     *
     * Creates an error view model from the message and renders it through the view presenter.
     *
     * @param string $message Error message to display in the rendered alert.
     *
     * @return string Rendered alert markup.
     */
    private function render_alert( string $message ): string {
        $vm = Shortcode_View_Model::for_error( $message );

        return $this->view_presenter->render( $vm );
    }
}
