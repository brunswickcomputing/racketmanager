<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
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
        private readonly View_Renderer_Interface $renderer,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null,
        private readonly ?Option_Gateway_Interface $option_gateway = null
    ) {}

    public function render( Competition_View_Model $vm ): string {
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
        string $curr_season = '',
        array $seasons = [],
        int $header_level = 1,
        ?string $standings_template = null,
        string|int $post_id = '',
        string $pagename_query = '',
        string $date_format = ''
    ): Event_Header_View_Model {
        $raw_comp_type = $event->competition->type ?? '';
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
            $competition_link = '/' . Util::seo_url( (string) $competition_name ) . '/' . $curr_season . '/';
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
            curr_season: $curr_season
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
}
