<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Application\Competition\DTOs\Competition_Page_Result;
use Racketmanager\Presentation\View_Models\Competition_Header_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Competition_Overview_View_Model;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Presentation\View_Models\Event_Header_View_Model;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Util\Util;
use Throwable;
use function __;
use function get_option;
use function get_permalink;
use function mysql2date;
use function ob_end_clean;
use function ob_get_level;
use function sprintf;
use function Racketmanager\show_alert;

/**
 * Presenter for [competition] shortcode view model.
 */
class Competition_Shortcode_Presenter {
    public function __construct(
        private readonly ?View_Renderer_Interface $renderer = null,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Option_Gateway_Interface $option_gateway = null
    ) {}

    public function render( Competition_View_Model|Competition_Page_Result $vm ): string {
        if ( $vm instanceof Competition_Page_Result ) {
            $vm = new Competition_View_Model(
                template: $vm->template,
                competition: $vm->competition,
                competition_season: $vm->competition_season,
                tab: $vm->tab,
                seasons: $vm->seasons,
                error_message: $vm->error_message,
                menu_options: $vm->menu_options,
                is_singular: $vm->is_singular,
                post_id: $vm->post_id ?? '',
                pagename: $vm->pagename,
                competition_seo_name: $vm->competition_seo_name
            );
        }

        $default_vars = [
            'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
            'player_presenter' => $this->player_presenter ?? new Player_Presenter( $this ),
        ];

        if ( null !== $vm->error_message ) {
            $vars = array_merge( $default_vars, [
                'msg'   => $vm->error_message,
                'class' => 'danger',
            ] );
            return $this->renderer->render_to_string( 'alert', [], $vars );
        }

        $vars = array_merge( $default_vars, $vm->template_vars() );
        if ( ! isset( $vars['competition_header'] ) && isset( $vm->competition ) ) {
            $vars['competition_header'] = $this->present_header(
                competition: $vm->competition,
                competition_season: $vm->competition_season,
                season: null,
                seasons: $vm->seasons,
                post_id: '' !== $vm->post_id ? $vm->post_id : ( function_exists( 'get_the_ID' ) && false !== get_the_ID() ? (int) get_the_ID() : '' ),
                pagename: '' !== $vm->pagename ? $vm->pagename : Util::seo_url( (string) ( $vm->competition->name ?? '' ) ),
            );
        }

        $level = ob_get_level();
        try {
            return $this->renderer->render_to_string( $vm->template, $vm, $vars );
        } catch ( Template_Not_Found_Exception ) {
            $this->restore_buffer_level( $level );
            /* translators: %1$s: template %2$s: directory */
            $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $vm->template, 'templates' );

            $error_vars = array_merge( $default_vars, [
                'msg'   => $msg,
                'class' => 'danger',
            ] );
            return $this->renderer->render_to_string( 'alert', [], $error_vars );
        } catch ( Throwable $e ) {
            $this->restore_buffer_level( $level );
            throw $e;
        }
    }

    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }

    /**
     * Build the display header view model for a competition.
     *
     * @param object $competition
     * @param array<string, mixed> $competition_season
     * @param string|null $season
     * @param array<int, array<string, mixed>> $seasons
     * @param string|int $post_id
     * @param string $pagename
     * @param string $date_format
     * @return Competition_Header_View_Model
     */
    public function present_header(
        object $competition,
        array $competition_season = [],
        ?string $season = null,
        array $seasons = [],
        string|int $post_id = '',
        string $pagename = '',
        string $date_format = ''
    ): Competition_Header_View_Model {
        $type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? 'league' );
        if ( $type instanceof \BackedEnum ) {
            $type = $type->value;
        }
        $type = (string) $type;

        $icon_path = match ( $type ) {
            'league'     => 'assets/icons/bootstrap-icons.svg#table',
            'cup'        => 'assets/icons/bootstrap-icons.svg#trophy-fill',
            'tournament' => 'assets/icons/lta-icons.svg#icon-bracket',
            default      => null,
        };

        if ( '' === $date_format ) {
            $date_format = (string) ( $this->option_gateway
            ? $this->option_gateway->get_option( 'date_format', 'd/m/Y' )
            : get_option( 'date_format', 'd/m/Y' ) );
        }

        $date_start = $competition_season['date_start'] ?? null;
        $date_end   = $competition_season['date_end'] ?? null;
        $range      = Date_Range::try_create( $date_start, $date_end );
        if ( $range ) {
            $has_date_range     = true;
            $date_start_display = (string) mysql2date( $date_format, $range->get_start_string() );
            $date_end_display   = (string) mysql2date( $date_format, $range->get_end_string() );
        } else {
            $has_date_range     = ! empty( $date_start ) && ! empty( $date_end );
            $date_start_display = $has_date_range ? (string) mysql2date( $date_format, (string) $date_start ) : null;
            $date_end_display   = $has_date_range ? (string) mysql2date( $date_format, (string) $date_end ) : null;
        }

        $form_action = ( '' !== (string) $post_id ) ? (string) get_permalink( (int) $post_id ) : '';

        return new Competition_Header_View_Model(
            name: (string) ( $competition->name ?? '' ),
            season_title: empty( $season ) ? null : (string) $season,
            icon_path: $icon_path,
            venue_name: ! empty( $competition_season['venue_name'] ) ? (string) $competition_season['venue_name'] : null,
            date_start_display: $date_start_display,
            date_end_display: $date_end_display,
            has_date_range: $has_date_range,
            entry_link: ! empty( $competition->entry_link ) ? (string) $competition->entry_link : null,
            show_season_selector: empty( $season ),
            post_id: $post_id,
            pagename: $pagename,
            form_action: $form_action,
            seasons: $seasons,
            current_season_name: (string) ( $competition_season['name'] ?? '' )
        );
    }

    /**
     * Build the display header view model for an event.
     *
     * @param object $event
     * @param object|null $club
     * @param string $curr_season
     * @param array<int, mixed> $seasons
     * @param int $header_level
     * @param string|null $standings_template
     * @param string|int $post_id
     * @param string $pagename_query
     * @param string $date_format
     * @return Event_Header_View_Model
     */
    public function present_event_header(
        object $event,
        ?object $club = null,
        string|int $curr_season = '',
        array $seasons = [],
        int $header_level = 1,
        ?string $standings_template = null,
        string|int $post_id = '',
        string $pagename_query = '',
        string $date_format = ''
    ): Event_Header_View_Model {
        $curr_season_str = (string) $curr_season;
        $raw_comp_type   = $event->competition->type ?? '';
        $competition_type = $raw_comp_type instanceof \BackedEnum ? $raw_comp_type->value : (string) $raw_comp_type;

        $icon_path = match ( $competition_type ) {
            'league'     => 'assets/icons/bootstrap-icons.svg#table',
            'cup'        => 'assets/icons/bootstrap-icons.svg#trophy-fill',
            'tournament' => 'assets/icons/lta-icons.svg#icon-bracket',
            default      => null,
        };

        $event_name = (string) ( $event->name ?? '' );
        if ( 'club/event' === $pagename_query && $club ) {
            $club_shortcode = (string) ( $club->shortcode ?? '' );
            $pagename       = '/clubs/' . Util::seo_url( $club_shortcode ) . '/event/' . Util::seo_url( $event_name ) . '/';
        } elseif ( 'tournament' === $competition_type ) {
            $pagename = $pagename_query;
        } else {
            $pagename = '/' . $competition_type . 's/' . Util::seo_url( $event_name ) . '/';
        }

        $is_box = ! empty( $event->is_box );
        $season_label = $is_box ? __( 'Round', 'racketmanager' ) : __( 'Season', 'racketmanager' );

        $competition_name = $event->competition->name ?? null;
        $competition_link = null;
        if ( ! empty( $competition_name ) ) {
            $competition_link = '/' . Util::seo_url( (string) $competition_name ) . '/' . $curr_season_str . '/';
        }

        if ( '' === $date_format ) {
            $date_format = (string) ( $this->option_gateway
            ? $this->option_gateway->get_option( 'date_format', 'd/m/Y' )
            : get_option( 'date_format', 'd/m/Y' ) );
        }

        $date_start = $event->competition->current_season['date_start'] ?? null;
        $date_end   = $event->competition->current_season['date_end'] ?? null;
        $has_date_range = ! empty( $date_start ) && ! empty( $date_end );

        $date_start_display = $has_date_range ? (string) mysql2date( $date_format, (string) $date_start ) : null;
        $date_end_display   = $has_date_range ? (string) mysql2date( $date_format, (string) $date_end ) : null;

        $show_season_selector = empty( $standings_template ) || 'constitution' !== $standings_template;
        $form_action = ( '' !== (string) $post_id ) ? (string) get_permalink( (int) $post_id ) : '';

        return new Event_Header_View_Model(
            name: $event_name,
            header_level: $header_level ?: 1,
            icon_path: $icon_path,
            competition_name: $competition_name ? (string) $competition_name : null,
            competition_link: $competition_link,
            date_start_display: $date_start_display,
            date_end_display: $date_end_display,
            has_date_range: $has_date_range,
            show_season_selector: $show_season_selector,
            post_id: $post_id,
            pagename: $pagename,
            form_action: $form_action,
            season_label: $season_label,
            seasons: $seasons,
            curr_season: $curr_season_str
        );
    }

    /**
     * Build view models for items in a competition list.
     *
     * @param array<int, mixed> $competitions
     * @param string $competition_type
     * @param object|null $player
     * @param string $date_format
     * @return array<int, Competition_List_Item_View_Model>
     */
    public function present_competition_list(
        array $competitions,
        string $competition_type,
        ?object $player = null,
        string $date_format = 'j M Y'
    ): array {
        $items = [];
        foreach ( $competitions as $competition ) {
            if ( $competition instanceof Competition_List_Item_View_Model ) {
                $items[] = $competition;
                continue;
            }

            $icon_path = match ( $competition_type ) {
                'league'     => 'assets/icons/bootstrap-icons.svg#table',
                'tournament',
                'cup'        => 'assets/icons/lta-icons.svg#icon-bracket',
                default      => null,
            };

            $comp_name = (string) ( $competition->name ?? '' );
            if ( 'tournament' === $competition_type ) {
                $competition_link = '/tournament/' . Util::seo_url( $comp_name ) . '/';
                $display_name     = $comp_name;
            } else {
                $competition_link = '/' . Util::seo_url( $comp_name ) . '/';
                if ( empty( $competition->season ) ) {
                    $display_name = $comp_name;
                } else {
                    $competition_link .= $competition->season . '/';
                    $display_name      = $comp_name . ' ' . $competition->season;
                }
            }

            if ( ! empty( $player ) ) {
                $player_name = (string) ( $player->display_name ?? '' );
                $competition_link .= 'player/' . Util::seo_url( $player_name ) . '/';
            }

            $date_start = $competition->date_start ?? null;
            $date_end   = $competition->date_end ?? null;
            $has_date_range = ! empty( $date_start ) && ! empty( $date_end );

            $date_start_display = $has_date_range ? (string) mysql2date( $date_format, (string) $date_start ) : null;
            $date_end_display   = $has_date_range ? (string) mysql2date( $date_format, (string) $date_end ) : null;

            $is_open   = ! empty( $competition->is_open );
            $entry_url = null;
            if ( $is_open ) {
                $entry_url = '/' . Util::seo_url( $competition_type ) . '/entry-form/' . Util::seo_url( $display_name ) . '/';
                if ( 'tournament' !== $competition_type && ! empty( $competition->current_season['name'] ) ) {
                    $entry_url .= $competition->current_season['name'] . '/';
                }
            }

            $items[] = new Competition_List_Item_View_Model(
                name: $display_name,
                link: $competition_link,
                icon_path: $icon_path,
                venue_name: ! empty( $competition->venue_name ) ? (string) $competition->venue_name : null,
                date_start_display: $date_start_display,
                date_end_display: $date_end_display,
                has_date_range: $has_date_range,
                is_open: $is_open,
                entry_url: $entry_url
            );
        }

        return $items;
    }

    /**
     * Build view model for competition overview.
     *
     * @param object $competition
     * @param object $overview
     * @param array<string, mixed> $competition_season
     * @param string $date_format
     * @return Competition_Overview_View_Model
     */
    public function present_overview(
        object $competition,
        object $overview,
        array $competition_season = [],
        string $date_format = ''
    ): Competition_Overview_View_Model {
        if ( '' === $date_format ) {
            $date_format = (string) ( $this->option_gateway
            ? $this->option_gateway->get_option( 'date_format', 'd/m/Y' )
            : get_option( 'date_format', 'd/m/Y' ) );
        }

        $timeline_items = [];
        if ( ! empty( $competition_season['date_end'] ) ) {
            if ( ! empty( $competition_season['date_open'] ) ) {
                $is_open = (bool) ( $competition->is_open ?? false );
                $timeline_items[] = [
                    'key'           => 'entry_open',
                    'label'         => __( 'Entry opens', 'racketmanager' ),
                    'date'          => (string) mysql2date( $date_format, (string) $competition_season['date_open'] ),
                    'item_class'    => 'is-entry-open',
                    'current_class' => $is_open ? 'is-current is-success' : '',
                ];
            }
            if ( ! empty( $competition_season['date_closing'] ) ) {
                $is_closed = (bool) ( $competition->is_closed ?? false );
                $timeline_items[] = [
                    'key'           => 'closing_deadline',
                    'label'         => __( 'Closing deadline', 'racketmanager' ),
                    'date'          => (string) mysql2date( $date_format, (string) $competition_season['date_closing'] ),
                    'item_class'    => 'is-entry-closed',
                    'current_class' => $is_closed ? 'is-current is-warning' : '',
                ];
            }
            if ( ! empty( $competition_season['date_start'] ) ) {
                $is_started = (bool) ( $competition->is_started ?? false );
                $timeline_items[] = [
                    'key'           => 'start_competition',
                    'label'         => __( 'Start competition', 'racketmanager' ),
                    'date'          => (string) mysql2date( $date_format, (string) $competition_season['date_start'] ),
                    'item_class'    => 'is-started',
                    'current_class' => $is_started ? 'is-current is-success' : '',
                ];
            }
            if ( ! empty( $competition_season['date_end'] ) ) {
                $is_complete = (bool) ( $competition->is_complete ?? false );
                $timeline_items[] = [
                    'key'           => 'end_competition',
                    'label'         => __( 'End of competition', 'racketmanager' ),
                    'date'          => (string) mysql2date( $date_format, (string) $competition_season['date_end'] ),
                    'item_class'    => 'is-finished',
                    'current_class' => $is_complete ? 'is-current is-danger' : '',
                ];
            }
        }

        return new Competition_Overview_View_Model(
            venue_name: ! empty( $competition_season['venue_name'] ) ? (string) $competition_season['venue_name'] : null,
            num_events: (int) ( $overview->num_events ?? 0 ),
            num_teams: (int) ( $overview->num_teams ?? 0 ),
            num_players: ! empty( $overview->num_players ) ? (int) $overview->num_players : null,
            grade: ! empty( $overview->grade ) ? (string) $overview->grade : null,
            timeline_items: $timeline_items
        );
    }

    /**
     * Build the competition rules permalink.
     */
    public function get_rules_url( object $competition ): string {
        $name = (string) ( $competition->name ?? ( method_exists( $competition, 'get_name' ) ? $competition->get_name() : '' ) );

        return '/rules/' . Util::seo_url( $name ) . '-rules/';
    }

    /**
     * Build the club permalink used as an entry-form back URL.
     */
    public function get_club_url( object $club ): string {
        if ( $this->club_presenter ) {
            return $this->club_presenter->get_link( $club );
        }
        $shortcode = (string) ( $club->shortcode ?? ( method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '' ) );

        return '/clubs/' . Util::seo_url( $shortcode ) . '/';
    }

    /**
     * Format a closing date string for display.
     *
     * @param string|null $date_closing
     * @return string
     */
    public function present_closing_date( ?string $date_closing ): string {
        if ( empty( $date_closing ) ) {
            return '';
        }
        $date_format = (string) ( $this->option_gateway
            ? $this->option_gateway->get_option( 'date_format', 'd/m/Y' )
            : get_option( 'date_format', 'd/m/Y' ) );
        return (string) mysql2date( $date_format, (string) $date_closing );
    }

    /**
     * Present link map for competition clubs list.
     *
     * @param object $competition
     * @param iterable $clubs
     * @return array<int, string>
     */
    public function present_competition_club_links( object $competition, iterable $clubs ): array {
        $links       = [];
        $comp_name   = (string) ( $competition->name ?? '' );
        $season_name = (string) ( $competition->current_season['name'] ?? '' );

        foreach ( $clubs as $club ) {
            $club_id           = is_object( $club ) ? (int) ( $club->id ?? 0 ) : (int) ( $club['id'] ?? 0 );
            $shortcode         = is_object( $club ) ? (string) ( $club->shortcode ?? '' ) : (string) ( $club['shortcode'] ?? '' );
            $links[ $club_id ] = '/' . Util::seo_url( $comp_name ) . '/' . $season_name . '/club/' . Util::seo_url( $shortcode ) . '/';
        }

        return $links;
    }

    /**
     * Present URLs and link maps for a single competition club view.
     *
     * @param object $competition
     * @param object $competition_club
     * @return array{club_url: string, team_links: array<int, array{team_link: string, league_link: string}>, player_links: array<int, string>}
     */
    public function present_competition_club_detail_links( object $competition, object $competition_club ): array {
        $comp_type       = $this->resolve_competition_type( $competition );
        $comp_name       = (string) ( $competition->name ?? '' );
        $season          = (string) ( $competition->current_season['name'] ?? '' );
        $shortcode       = (string) ( $competition_club->shortcode ?? '' );
        $is_championship = ! empty( $competition->is_championship );

        $club_url = '/clubs/' . Util::seo_url( $shortcode ) . '/';

        $team_links = [];
        if ( ! empty( $competition_club->teams ) && is_iterable( $competition_club->teams ) ) {
            foreach ( $competition_club->teams as $team ) {
                $team_id   = is_object( $team ) ? (int) ( $team->id ?? 0 ) : (int) ( $team['id'] ?? 0 );
                $team_name = is_object( $team ) ? (string) ( $team->name ?? '' ) : (string) ( $team['name'] ?? '' );
                if ( $is_championship ) {
                    $event_name  = is_object( $team ) ? (string) ( $team->event_name ?? '' ) : (string) ( $team['event_name'] ?? '' );
                    $team_link   = '/' . $comp_type . '/' . Util::seo_url( $event_name ) . '/' . $season . '/team/' . Util::seo_url( $team_name ) . '/';
                    $league_link = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/';
                } else {
                    $league_title = is_object( $team ) ? (string) ( $team->league_title ?? '' ) : (string) ( $team['league_title'] ?? '' );
                    $team_link    = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/team/' . Util::seo_url( $team_name ) . '/';
                    $league_link  = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/';
                }
                $team_links[ $team_id ] = [
                    'team_link'   => $team_link,
                    'league_link' => $league_link,
                ];
            }
        }

        $player_links = [];
        if ( ! empty( $competition_club->players ) && is_iterable( $competition_club->players ) ) {
            foreach ( $competition_club->players as $player ) {
                $player_id             = is_object( $player ) ? (int) ( $player->id ?? ( $player->ID ?? 0 ) ) : (int) ( $player['id'] ?? 0 );
                $player_name           = is_object( $player ) ? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->display_name ?? '' ) ) : ( $player['fullname'] ?? ( $player['display_name'] ?? '' ) );
                $player_links[ $player_id ] = '/' . Util::seo_url( $comp_name ) . '/' . $season . '/player/' . Util::seo_url( (string) $player_name ) . '/';
            }
        }

        return [
            'club_url'     => $club_url,
            'team_links'   => $team_links,
            'player_links' => $player_links,
        ];
    }

    /**
     * Present link map for competition teams list.
     *
     * @param object $competition
     * @param iterable $teams
     * @return array<int, array{team_link: string, club_link: string, league_link: string}>
     */
    public function present_competition_teams_links( object $competition, iterable $teams ): array {
        $comp_type = $this->resolve_competition_type( $competition );
        $comp_name = (string) ( isset( $competition->name ) ? $competition->name : '' );
        $season    = (string) ( $competition->current_season['name'] ?? '' );

        $links = [];
        foreach ( $teams as $team ) {
            $team_id        = is_object( $team ) ? (int) ( $team->team_id ?? ( $team->id ?? 0 ) ) : (int) ( $team['team_id'] ?? ( $team['id'] ?? 0 ) );
            $league_name    = is_object( $team ) ? (string) ( $team->league_name ?? '' ) : (string) ( $team['league_name'] ?? '' );
            $team_name      = is_object( $team ) ? (string) ( $team->team_name ?? ( $team->name ?? '' ) ) : (string) ( $team['team_name'] ?? ( $team['name'] ?? '' ) );
            if ( is_object( $team ) ) {
                $club_shortcode = is_object( $team->club ?? null ) ? (string) ( $team->club->shortcode ?? '' ) : (string) ( $team->club_shortcode ?? '' );
            } else {
                $club_shortcode = is_array( $team['club'] ?? null ) ? (string) ( $team['club']['shortcode'] ?? '' ) : (string) ( $team['club_shortcode'] ?? '' );
            }

            $links[ $team_id ] = [
                'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_name ) . '/' . $season . '/team/' . Util::seo_url( $team_name ) . '/',
                'club_link'   => '/' . Util::seo_url( $comp_name ) . '/' . $season . '/club/' . Util::seo_url( $club_shortcode ) . '/',
                'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_name ) . '/' . $season . '/',
            ];
        }

        return $links;
    }

    /**
     * Get base URL for competition player pages.
     *
     * @param object $competition
     * @return string
     */
    public function get_competition_player_base_url( object $competition ): string {
        $comp_name = (string) ( isset( $competition->name ) ? $competition->name : '' );
        $season    = (string) ( $competition->current_season['name'] ?? '' );
        return '/' . Util::seo_url( $comp_name ) . '/' . $season . '/player/';
    }

    /**
     * Present link map for competition events list.
     *
     * @param object $competition
     * @param iterable $events
     * @return array<int, string>
     */
    public function present_competition_events_links( object $competition, iterable $events ): array {
        $comp_type = $this->resolve_competition_type( $competition );
        $season    = (string) ( $competition->current_season['name'] ?? '' );

        $links = [];
        foreach ( $events as $event ) {
            $event_id           = is_object( $event ) ? (int) ( $event->event_id ?? ( $event->id ?? 0 ) ) : (int) ( $event['event_id'] ?? ( $event['id'] ?? 0 ) );
            $event_name         = is_object( $event ) ? (string) ( $event->event_name ?? ( $event->name ?? '' ) ) : (string) ( $event['event_name'] ?? ( $event['name'] ?? '' ) );
            $links[ $event_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/';
        }

        return $links;
    }

    /**
     * Present link map for grouped competition players.
     *
     * @param object $competition
     * @param iterable $grouped_players
     * @return array<int, string>
     */
    public function present_competition_players_links( object $competition, iterable $grouped_players ): array {
        $base_url = $this->get_competition_player_base_url( $competition );

        $links = [];
        foreach ( $grouped_players as $players ) {
            if ( is_iterable( $players ) ) {
                foreach ( $players as $player ) {
                    $player_id           = is_object( $player ) ? (int) ( $player->id ?? ( $player->ID ?? 0 ) ) : (int) ( $player['id'] ?? 0 );
                    $player_name         = is_object( $player ) ? (string) ( $player->display_name ?? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : '' ) ) : (string) ( $player['display_name'] ?? ( $player['fullname'] ?? '' ) );
                    $links[ $player_id ] = $base_url . Util::seo_url( $player_name ) . '/';
                }
            }
        }

        return $links;
    }

    /**
     * Get base URL for event player pages.
     *
     * @param object $event
     * @return string
     */
    public function get_event_player_base_url( object $event ): string {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( isset( $event->name ) ? $event->name : '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        return '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/player/';
    }

    /**
     * Present link map for event clubs list.
     *
     * @param object $event
     * @param iterable $clubs
     * @return array<int, string>
     */
    public function present_event_club_links( object $event, iterable $clubs ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( isset( $event->name ) ? $event->name : '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );

        $links = [];
        foreach ( $clubs as $club ) {
            $club_id           = is_object( $club ) ? (int) ( $club->id ?? 0 ) : (int) ( $club['id'] ?? 0 );
            $shortcode         = is_object( $club ) ? (string) ( $club->shortcode ?? '' ) : (string) ( $club['shortcode'] ?? '' );
            $links[ $club_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/club/' . Util::seo_url( $shortcode ) . '/';
        }

        return $links;
    }

    /**
     * Present URLs and link maps for a single event club view.
     *
     * @param object $event
     * @param object $event_club
     * @return array{club_url: string, team_links: array<int, array{team_link: string, league_link: string}>, player_links: array<int, string>}
     */
    public function present_event_club_detail_links( object $event, object $event_club ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( isset( $event->name ) ? $event->name : '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        $shortcode  = (string) ( $event_club->shortcode ?? '' );

        $club_url = '/clubs/' . Util::seo_url( $shortcode ) . '/';

        $team_links = [];
        if ( ! empty( $event_club->teams ) && is_iterable( $event_club->teams ) ) {
            foreach ( $event_club->teams as $team ) {
                $team_id      = is_object( $team ) ? (int) ( $team->id ?? 0 ) : (int) ( $team['id'] ?? 0 );
                $team_name    = is_object( $team ) ? (string) ( $team->name ?? '' ) : (string) ( $team['name'] ?? '' );
                $league_title = is_object( $team ) ? (string) ( $team->league_title ?? '' ) : (string) ( $team['league_title'] ?? '' );

                $team_links[ $team_id ] = [
                    'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/team/' . Util::seo_url( $team_name ) . '/',
                    'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/',
                ];
            }
        }

        $player_links = [];
        if ( ! empty( $event_club->players ) && is_iterable( $event_club->players ) ) {
            foreach ( $event_club->players as $player ) {
                $player_id             = is_object( $player ) ? (int) ( $player->id ?? ( $player->ID ?? 0 ) ) : (int) ( $player['id'] ?? 0 );
                $player_name           = is_object( $player ) ? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->display_name ?? '' ) ) : ( $player['fullname'] ?? ( $player['display_name'] ?? '' ) );
                $player_links[ $player_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/player/' . Util::seo_url( (string) $player_name ) . '/';
            }
        }

        return [
            'club_url'     => $club_url,
            'team_links'   => $team_links,
            'player_links' => $player_links,
        ];
    }

    /**
     * Present link map for event teams list.
     *
     * @param object $event
     * @param iterable $teams
     * @return array<int, array{team_link: string, club_link: string, league_link: string}>
     */
    public function present_event_teams_links( object $event, iterable $teams ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( isset( $event->name ) ? $event->name : '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        $is_box     = ! empty( $event->is_box );

        $links = [];
        foreach ( $teams as $team ) {
            $team_id        = is_object( $team ) ? (int) ( $team->id ?? 0 ) : (int) ( $team['id'] ?? 0 );
            $team_name      = is_object( $team ) ? (string) ( $team->name ?? '' ) : (string) ( $team['name'] ?? '' );
            $league_title   = is_object( $team ) ? (string) ( $team->league_title ?? '' ) : (string) ( $team['league_title'] ?? '' );
            if ( is_object( $team ) ) {
                $club_shortcode = is_object( $team->club ?? null ) ? (string) ( $team->club->shortcode ?? '' ) : (string) ( $team->club_shortcode ?? '' );
            } else {
                $club_shortcode = is_array( $team['club'] ?? null ) ? (string) ( $team['club']['shortcode'] ?? '' ) : (string) ( $team['club_shortcode'] ?? '' );
            }

            if ( $is_box ) {
                $league_link = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . __( 'round', 'racketmanager' ) . '-' . $season . '/';
            } else {
                $league_link = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/';
            }

            $links[ $team_id ] = [
                'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/team/' . Util::seo_url( $team_name ) . '/',
                'club_link'   => '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/club/' . Util::seo_url( $club_shortcode ) . '/',
                'league_link' => $league_link,
            ];
        }

        return $links;
    }

    /**
     * Present link map for grouped event players.
     *
     * @param object $event
     * @param iterable $grouped_players
     * @return array<int, string>
     */
    public function present_event_players_links( object $event, iterable $grouped_players ): array {
        $base_url = $this->get_event_player_base_url( $event );

        $links = [];
        foreach ( $grouped_players as $players ) {
            if ( is_iterable( $players ) ) {
                foreach ( $players as $player ) {
                    $player_id           = is_object( $player ) ? (int) ( $player->id ?? ( $player->ID ?? 0 ) ) : (int) ( $player['id'] ?? 0 );
                    $player_name         = is_object( $player ) ? (string) ( $player->display_name ?? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : '' ) ) : (string) ( $player['display_name'] ?? ( $player['fullname'] ?? '' ) );
                    $links[ $player_id ] = $base_url . Util::seo_url( $player_name ) . '/';
                }
            }
        }

        return $links;
    }

    /**
     * Present link map for event league URLs.
     *
     * @param object $event
     * @param iterable $leagues
     * @param string|null $season
     * @param string $base_url
     * @return array<int, string>
     */
    public function present_event_league_urls( object $event, iterable $leagues, ?string $season = null, string $base_url = '' ): array {
        $is_box      = ! empty( $event->is_box );
        $season_name = $season ?? (string) ( $event->current_season['name'] ?? '' );

        $links = [];
        foreach ( $leagues as $league ) {
            $league_id    = is_object( $league ) ? (int) ( $league->id ?? 0 ) : (int) ( $league['id'] ?? 0 );
            $league_title = is_object( $league ) ? (string) ( $league->title ?? '' ) : (string) ( $league['title'] ?? '' );

            $href = $base_url . '/' . __( 'league', 'racketmanager' ) . '/' . Util::seo_url( $league_title ) . '/';
            if ( $is_box ) {
                $href .= __( 'round', 'racketmanager' ) . '-';
            }
            $href .= $season_name . '/';

            $links[ $league_id ] = $href;
        }

        return $links;
    }

    /**
     * Present link map for event standings leagues.
     *
     * @param object $event
     * @param iterable $leagues
     * @return array<int, string>
     */
    public function present_event_standings_links( object $event, iterable $leagues ): array {
        return $this->present_event_league_urls( $event, $leagues );
    }

    /**
     * Present event and league links for player match groupings.
     *
     * @param iterable $matches_groups
     * @return array<string, array{event_link: string, league_link: string}>
     */
    public function present_player_match_group_links( iterable $matches_groups ): array {
        $links = [];
        foreach ( $matches_groups as $key => $player_matches ) {
            $league = is_object( $player_matches ) ? ( $player_matches->league ?? null ) : ( $player_matches['league'] ?? null );
            if ( $league && isset( $league->event ) ) {
                $league_comp  = $league->event->competition ?? null;
                $comp_type    = $this->resolve_competition_type( $league_comp );
                $event_name   = (string) ( isset( $league->event->name ) ? $league->event->name : '' );
                $league_title = (string) ( $league->title ?? '' );
                $season       = (string) ( $league->season ?? '' );

                $links[ (string) $key ] = [
                    'event_link'  => '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/',
                    'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/',
                ];
            }
        }

        return $links;
    }

    /**
     * Resolve competition type string from an object or value.
     *
     * @param mixed $competition
     * @return string
     */
    private function resolve_competition_type( mixed $competition ): string {
        if ( is_object( $competition ) ) {
            $type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? 'league' );
        } else {
            $type = $competition ?? 'league';
        }

        if ( $type instanceof BackedEnum ) {
            return (string) $type->value;
        }

        return '' !== (string) $type ? (string) $type : 'league';
    }

    /**
     * Present winners grouped by category for competition winners view.
     *
     * @param array $winners
     * @return array
     */
    public function present_competition_winners( array $winners ): array {
        $presented = [];
        foreach ( $winners as $key => $winner_group ) {
            $presented_group = [];
            foreach ( $winner_group as $winner ) {
                $w = is_object( $winner ) ? clone $winner : (object) $winner;
                $comp_type  = $w->competition_type ?? 'competition';
                $event_name = $w->event_name ?? '';
                $season     = $w->season ?? '';
                $w->link_url        = '/' . Util::seo_url( (string) $comp_type ) . 's/' . Util::seo_url( (string) $event_name ) . '/' . Util::seo_url( (string) $season ) . '/';
                $w->link_class      = null;
                $w->data_season     = null;
                $w->data_type       = null;
                $w->data_type_id    = null;
                $w->data_type_type  = null;
                $presented_group[]  = $w;
            }
            $presented[ $key ] = $presented_group;
        }

        return $presented;
    }

    /**
     * Present link map for club event player stats.
     *
     * @param object $event
     * @param iterable $player_stats
     * @param string|null $season_name
     * @return array<string, string>
     */
    public function present_club_event_player_links( object $event, iterable $player_stats, ?string $season_name = null ): array {
        $player_links     = [];
        $comp_type        = ( isset( $event->competition ) && isset( $event->competition->type ) ) ? $event->competition->type : 'league';
        $competition_type = $comp_type instanceof BackedEnum ? $comp_type->value : (string) $comp_type;
        $event_slug       = Util::seo_url( (string) ( $event->name ?? '' ) );
        $season_slug      = (string) ( $season_name ?? ( $event->current_season['name'] ?? '' ) );

        if ( ! empty( $player_stats ) ) {
            foreach ( $player_stats as $stat ) {
                $fn = is_object( $stat ) ? ( $stat->fullname ?? '' ) : ( $stat['fullname'] ?? '' );
                if ( '' !== (string) $fn ) {
                    $player_links[ (string) $fn ] = '/' . $competition_type . 's/' . $event_slug . '/' . $season_slug . '/player/' . Util::seo_url( (string) $fn ) . '/';
                }
            }
        }

        return $player_links;
    }

    /**
     * Present event draw leagues with precomputed match URLs, opponent details, and score models.
     *
     * @param object $event
     * @return object
     */
    public function present_event_draw( object $event ): object {
        $presented_event = clone $event;
        if ( ! empty( $presented_event->leagues ) && is_array( $presented_event->leagues ) ) {
            $presented_leagues = [];
            foreach ( $presented_event->leagues as $l_idx => $league ) {
                $league_obj = is_object( $league ) ? clone $league : (object) $league;
                if ( ! empty( $league_obj->finals ) && is_array( $league_obj->finals ) ) {
                    $competition = ( isset( $league_obj->event->competition ) ? $league_obj->event->competition : null ) ?? ( $presented_event->competition ?? null );
                    $league_obj->finals = Draw_Presenter::present_finals(
                        $league_obj->finals,
                        null,
                        $league_obj,
                        $competition,
                        $presented_event
                    );
                }
                $presented_leagues[ $l_idx ] = $league_obj;
            }
            $presented_event->leagues = $presented_leagues;
        }

        return $presented_event;
    }

    /**
     * Present club entry form urls map.
     *
     * @param array $clubs
     * @return array<int|string, string>
     */
    public function present_entry_form_club_urls( array $clubs ): array {
        $club_urls = [];
        foreach ( $clubs as $c ) {
            $c_id        = is_object( $c ) ? ( $c->id ?? 0 ) : ( $c['id'] ?? 0 );
            $c_shortcode = is_object( $c ) ? ( $c->shortcode ?? '' ) : ( $c['shortcode'] ?? '' );
            $slug        = Util::seo_url( (string) $c_shortcode );
            if ( '' !== (string) $c_id ) {
                $club_urls[ (string) $c_id ] = $slug . '/';
                $club_urls[ (int) $c_id ]    = $slug . '/';
            }
            if ( '' !== (string) $c_shortcode ) {
                $club_urls[ (string) $c_shortcode ] = $slug . '/';
            }
        }
        return $club_urls;
    }
}
