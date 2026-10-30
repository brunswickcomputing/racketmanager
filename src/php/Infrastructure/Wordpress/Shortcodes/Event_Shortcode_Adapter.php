<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use BackedEnum;
use Racketmanager\Application\Event\DTOs\Event_Clubs_Result;
use Racketmanager\Application\Event\DTOs\Event_Draw_Result;
use Racketmanager\Application\Event\DTOs\Event_Dropdown_Result;
use Racketmanager\Application\Event\DTOs\Event_Matches_Result;
use Racketmanager\Application\Event\DTOs\Event_Page_Result;
use Racketmanager\Application\Event\DTOs\Event_Partner_Result;
use Racketmanager\Application\Event\DTOs\Event_Players_Result;
use Racketmanager\Application\Event\DTOs\Event_Standings_Result;
use Racketmanager\Application\Event\DTOs\Event_Team_Matches_Result;
use Racketmanager\Application\Event\DTOs\Event_Teams_Result;
use Racketmanager\Application\Event\DTOs\Team_Order_Players_Result;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Query;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util;
use function is_array;
use function is_numeric;
use function is_object;
use function method_exists;
use function str_replace;

/**
 * Driving adapter for Event shortcodes.
 */
class Event_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly ?RacketManager $racketmanager = null,
        private readonly ?Shortcode_View_Presenter $view_presenter = null,
        private readonly ?Get_Event_Handler $event_handler = null,
        private readonly ?Get_Event_Standings_Handler $standings_handler = null,
        private readonly ?Get_Event_Draw_Handler $draw_handler = null,
        private readonly ?Get_Event_Matches_Handler $matches_handler = null,
        private readonly ?Get_Event_Clubs_Handler $clubs_handler = null,
        private readonly ?Get_Event_Teams_Handler $teams_handler = null,
        private readonly ?Get_Event_Players_Handler $players_handler = null,
        private readonly ?Get_Event_Partner_Handler $partner_handler = null,
        private readonly ?Get_Event_Team_Matches_Handler $team_matches_handler = null,
        private readonly ?Get_Team_Order_Players_Handler $team_order_players_handler = null,
        private readonly ?Get_Event_Dropdown_Handler $dropdown_handler = null,
        private readonly ?Shortcode_Template_Renderer $template_renderer = null,
        private readonly ?Competition_Shortcode_Presenter $competition_shortcode_presenter = null,
        private readonly ?Tournament_Presenter $tournament_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Team_Details_Presenter $team_details_presenter = null
    ) {}

    public function show_event( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->event_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_event( $result ) );
    }

    public function show_event_standings( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Standings_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->standings_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_standings( $result ) );
    }

    public function show_event_draw( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Draw_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->draw_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_draw( $result ) );
    }

    public function show_event_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Matches_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->matches_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_matches( $result ) );
    }

    public function show_event_clubs( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Clubs_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->clubs_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_clubs( $result ) );
    }

    public function show_event_teams( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Teams_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->teams_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_teams( $result ) );
    }

    public function show_event_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Players_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->players_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_players( $result ) );
    }

    public function show_event_partner( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Partner_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->partner_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '', 'modal' ) );
        }

        return $this->render_vm( $this->present_partner( $result ) );
    }

    public function show_event_team_matches( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Team_Matches_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->team_matches_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_team_matches( $result ) );
    }

    public function show_team_order_players( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Team_Order_Players_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->team_order_players_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_team_order_players( $result ) );
    }

    public function show_dropdown( array|string $atts = [] ): string {
        $query_vars = $this->extract_query_vars();
        $query      = Get_Event_Dropdown_Query::from_atts( $this->normalize_atts( $atts, $query_vars ), $query_vars );

        $result = $this->dropdown_handler?->handle( $query );
        if ( ! $result ) {
            return '';
        }

        if ( $result->is_error() ) {
            return $this->render_vm( Shortcode_View_Model::for_error( $result->error_message ?? '' ) );
        }

        return $this->render_vm( $this->present_dropdown( $result ) );
    }

    public function present_event( Event_Page_Result $result ): Shortcode_View_Model {
        $comp_presenter = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();

        $event_header = $result->event ? $comp_presenter->present_event_header(
            event: $result->event,
            curr_season: $result->curr_season,
            seasons: $result->seasons,
            header_level: 1,
            post_id: $result->post_id,
            pagename_query: $result->pagename
        ) : null;

        $vars = [
            'event'            => $result->event,
            'seasons'          => $result->seasons,
            'curr_season'      => $result->curr_season,
            'tab'              => $result->tab,
            'menu_options'     => $result->menu_options,
            'is_singular'      => $result->is_singular,
            'competition_type' => $result->competition_type,
            'event_seo_name'   => $result->event_seo_name,
            'event_header'     => $event_header,
        ];

        return Shortcode_View_Model::for_template(
            $result->template,
            $vars,
            false
        );
    }

    public function present_standings( Event_Standings_Result $result ): Shortcode_View_Model {
        $comp_presenter = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();

        $league_links = $result->event ? $comp_presenter->present_event_standings_links( $result->event, $result->leagues ) : [];

        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event'        => $result->event,
                'league_links' => $league_links,
            ],
            'event'
        );
    }

    public function present_draw( Event_Draw_Result $result ): Shortcode_View_Model {
        $comp_presenter = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();

        $event = $result->event ? $comp_presenter->present_event_draw( $result->event ) : null;

        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event' => $event,
            ],
            'event'
        );
    }

    public function present_matches( Event_Matches_Result $result ): Shortcode_View_Model {
        $tourn_presenter = $this->tournament_presenter ?? new Tournament_Presenter();

        $event = $result->event;
        if ( $event && ! empty( $event->leagues ) && is_array( $event->leagues ) ) {
            foreach ( $event->leagues as $l => $league ) {
                if ( isset( $league->finals ) && is_array( $league->finals ) ) {
                    foreach ( $league->finals as $f => $final ) {
                        if ( isset( $final->matches ) && is_array( $final->matches ) ) {
                            $final->matches = $tourn_presenter->present_match_list(
                                $final->matches,
                                $tourn_presenter->is_tournament_competition( $event->competition ?? null ),
                                [ 'show_header' => false ]
                            );
                        }
                    }
                }
            }
        }

        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event' => $event,
            ],
            'event'
        );
    }

    public function present_clubs( Event_Clubs_Result $result ): Shortcode_View_Model {
        $comp_presenter  = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();
        $tourn_presenter = $this->tournament_presenter ?? new Tournament_Presenter();

        $event      = $result->event;
        $event_club = $result->event_club;

        if ( $event && $event_club ) {
            $event_club_id = method_exists( $event_club, 'get_id' ) ? $event_club->get_id() : ( $event_club->id ?? null );
            if ( ! empty( $event_club->matches ) && is_array( $event_club->matches ) ) {
                foreach ( $event_club->matches as $key => $group_matches ) {
                    $event_club->matches[ $key ] = $tourn_presenter->present_match_group(
                        $key,
                        $group_matches,
                        $tourn_presenter->is_tournament_competition( $event->competition ?? null ),
                        [
                            'current_club' => $event_club_id,
                            'show_header'  => true,
                            'by_date'      => true,
                        ]
                    );
                }
            }
            if ( ! empty( $event_club->results ) && is_array( $event_club->results ) ) {
                foreach ( $event_club->results as $key => $group_matches ) {
                    $event_club->results[ $key ] = $tourn_presenter->present_match_group(
                        $key,
                        $group_matches,
                        $tourn_presenter->is_tournament_competition( $event->competition ?? null ),
                        [
                            'current_club' => $event_club_id,
                            'show_header'  => true,
                            'by_date'      => true,
                        ]
                    );
                }
            }
        }

        $vars = [
            'event'      => $event,
            'event_club' => $event_club,
        ];

        if ( $event && $event_club ) {
            $club_details         = $comp_presenter->present_event_club_detail_links( $event, $event_club );
            $vars['club_url']     = $club_details['club_url'];
            $vars['team_links']   = $club_details['team_links'];
            $vars['player_links'] = $club_details['player_links'];
        } elseif ( $event ) {
            $vars['club_links']   = $comp_presenter->present_event_club_links( $event, $result->clubs );
        }

        return Shortcode_View_Model::for_template(
            $result->template,
            $vars,
            'event'
        );
    }

    public function present_teams( Event_Teams_Result $result ): Shortcode_View_Model {
        $comp_presenter    = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();
        $tourn_presenter   = $this->tournament_presenter ?? new Tournament_Presenter();
        $details_presenter = $this->team_details_presenter ?? new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );

        $event = $result->event;
        $team  = $result->team;

        if ( $event && $team && ! empty( $team->matches ) && is_array( $team->matches ) ) {
            $team->matches = ( $event->competition->is_player_entry ?? false )
                ? $tourn_presenter->present_match_cards( $team->matches, null, null, '', false, $tourn_presenter->is_tournament_competition( $event->competition ) )
                : $tourn_presenter->present_match_list(
                    $team->matches,
                    $tourn_presenter->is_tournament_competition( $event->competition ?? null ),
                    [
                        'current_team' => (int) $team->id,
                        'show_header'  => false,
                    ]
                );
        }

        $team_rows = [];
        if ( $event && null === $team && 'teams-list' === $result->template ) {
            $comp_type = isset( $event->competition->type )
                ? ( $event->competition->type instanceof BackedEnum ? $event->competition->type->value : (string) $event->competition->type )
                : 'event';
            $item_link = '/' . $comp_type . '/' . Util::seo_url( (string) $event->name ) . '/' . $result->curr_season;
            $team_rows = $details_presenter->present_team_rows(
                $result->teams,
                $event,
                $item_link
            );
        }

        $team_links = $event ? $comp_presenter->present_event_teams_links( $event, $result->teams ) : [];

        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event'       => $event,
                'tab'         => 'teams',
                'curr_season' => $result->curr_season,
                'team_rows'   => $team_rows,
                'team_links'  => $team_links,
            ],
            'event'
        );
    }

    public function present_players( Event_Players_Result $result ): Shortcode_View_Model {
        $comp_presenter   = $this->competition_shortcode_presenter ?? new Competition_Shortcode_Presenter();
        $player_presenter = $this->player_presenter ?? new Player_Presenter( $comp_presenter );

        $event             = $result->event;
        $player_vm         = null;
        $match_group_links = [];
        $player_links      = [];
        $player_link       = null;

        if ( $result->player ) {
            $player_vm         = $player_presenter->present_player( $result->player );
            $match_group_links = $comp_presenter->present_player_match_group_links( $result->player->matches ?? [] );
        } elseif ( $event ) {
            $player_links = $comp_presenter->present_event_players_links( $event, $result->players );
            $player_link  = $comp_presenter->get_event_player_base_url( $event );
        }

        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event'             => $event,
                'player_vm'         => $player_vm,
                'match_group_links' => $match_group_links,
                'player_links'      => $player_links,
                'player_link'       => $player_link,
            ],
            'event'
        );
    }

    public function present_partner( Event_Partner_Result $result ): Shortcode_View_Model {
        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'event'          => $result->event,
                'player_id'      => $result->player_id,
                'partner_name'   => $result->partner_name,
                'partner_btm'    => $result->partner_btm,
                'partner_id'     => $result->partner_id,
                'partner_gender' => $result->partner_gender,
                'date_end'       => $result->date_end,
                'season'         => $result->season,
                'modal'          => $result->modal,
                'tournament_id'  => $result->tournament_id,
            ],
            $result->template_type
        );
    }

    public function present_team_matches( Event_Team_Matches_Result $result ): Shortcode_View_Model {
        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'matches' => $result->matches,
            ],
            'event'
        );
    }

    public function present_team_order_players( Team_Order_Players_Result $result ): Shortcode_View_Model {
        return Shortcode_View_Model::for_template(
            $result->template,
            $result->vars,
            'event'
        );
    }

    public function present_dropdown( Event_Dropdown_Result $result ): Shortcode_View_Model {
        return Shortcode_View_Model::for_template(
            $result->template,
            [
                'leagues' => $result->leagues,
            ],
            'event'
        );
    }

    private function render_vm( Shortcode_View_Model $vm ): string {
        if ( $this->view_presenter ) {
            return $this->view_presenter->render( $vm );
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

    private function normalize_atts( array|string $atts, array $query_vars ): array {
        $atts_array = is_array( $atts ) ? $atts : [];

        if ( ! isset( $atts_array['id'] ) && ! isset( $atts_array['event_id'] ) ) {
            $event_id = $this->resolve_event_id( $atts_array, $query_vars );
            if ( $event_id ) {
                $atts_array['id'] = $event_id;
            } else {
                $event_name = $this->resolve_event_name( $atts_array, $query_vars );
                if ( $event_name ) {
                    $atts_array['event_name'] = $event_name;
                }
            }
        }

        return $atts_array;
    }

    private function resolve_event_id( array $atts, array $query_vars = [] ): ?int {
        if ( isset( $atts['id'] ) && is_numeric( $atts['id'] ) && (int) $atts['id'] > 0 ) {
            return (int) $atts['id'];
        }
        if ( isset( $atts['event_id'] ) && is_numeric( $atts['event_id'] ) && (int) $atts['event_id'] > 0 ) {
            return (int) $atts['event_id'];
        }
        if ( isset( $query_vars['event_id'] ) && is_numeric( $query_vars['event_id'] ) && (int) $query_vars['event_id'] > 0 ) {
            return (int) $query_vars['event_id'];
        }
        if ( isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) && ! empty( $GLOBALS['event']->id ) && is_numeric( $GLOBALS['event']->id ) ) {
            return (int) $GLOBALS['event']->id;
        }

        return null;
    }

    private function resolve_event_name( array $atts, array $query_vars = [] ): ?string {
        if ( ! empty( $atts['event_name'] ) ) {
            return (string) $atts['event_name'];
        }
        if ( ! empty( $atts['event'] ) ) {
            return (string) $atts['event'];
        }
        if ( ! empty( $query_vars['event'] ) ) {
            return str_replace( '-', ' ', (string) $query_vars['event'] );
        }
        if ( isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) && ! empty( $GLOBALS['event']->name ) ) {
            return (string) $GLOBALS['event']->name;
        }

        return null;
    }
}
