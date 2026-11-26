<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use DateTimeInterface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Application\Competition\DTOs\Competition_Page_Result;
use Racketmanager\Presentation\View_Models\Competition_Header_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Competition_Overview_View_Model;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Presentation\View_Models\Event_Header_View_Model;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Util\Util;
use Throwable;
use function __;
use function get_permalink;
use function mysql2date;
use function ob_end_clean;
use function ob_get_level;
use function sprintf;

/**
 * Presenter for [competition] shortcode view model.
 */
readonly class Competition_Shortcode_Presenter {
    private const string ICON_LEAGUE     = 'assets/icons/bootstrap-icons.svg#table';
    private const string ICON_CUP        = 'assets/icons/bootstrap-icons.svg#trophy-fill';
    private const string ICON_TOURNAMENT = 'assets/icons/lta-icons.svg#icon-bracket';

    private const string TYPE_LEAGUE     = 'league';
    private const string TYPE_CUP        = 'cup';
    private const string TYPE_TOURNAMENT = 'tournament';

    private const string PATH_CLUBS  = '/clubs/';
    private const string PATH_CLUB   = '/club/';
    private const string PATH_TEAM   = '/team/';
    private const string PATH_PLAYER = '/player/';
    private const string PATH_RULES  = '/rules/';

    private const string ALERT_TEMPLATE     = 'alert';
    private const string ALERT_CLASS_DANGER = 'danger';

    private const string DEFAULT_DATE_FORMAT = 'd/m/Y';

    /**
     * Create a presenter with renderer, club presenter, player presenter, and optional option gateway dependencies.
     *
     * @param View_Renderer_Interface       $renderer view renderer.
     * @param Club_Presenter                $club_presenter club presenter.
     * @param Player_Presenter              $player_presenter player presenter.
     * @param Option_Gateway_Interface|null $option_gateway option gateway.
     *
     * @return void
     */
    public function __construct(
        private View_Renderer_Interface $renderer,
        private Club_Presenter $club_presenter,
        private Player_Presenter $player_presenter,
        private ?Option_Gateway_Interface $option_gateway = null
    ) {}

    /**
     * Render a competition view model or page result to an HTML string.
     *
     * @param Competition_View_Model|Competition_Page_Result $vm competition view model or page result.
     *
     * @return string rendered template output.
     * @throws Throwable Error.
     */
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
            'club_presenter'   => $this->club_presenter,
            'player_presenter' => $this->player_presenter,
        ];

        if ( null !== $vm->error_message ) {
            $vars = array_merge(
                $default_vars,
                [
                    'msg'   => $vm->error_message,
                    'class' => self::ALERT_CLASS_DANGER,
                ]
            );
            return $this->renderer->render_to_string( self::ALERT_TEMPLATE, [], $vars );
        }

        $vars = array_merge( $default_vars, $vm->template_vars() );
        if ( ! isset( $vars['competition_header'] ) && isset( $vm->competition ) ) {
            $vars['competition_header'] = $this->present_header(
                competition: $vm->competition,
                competition_season: $vm->competition_season,
                season: null,
                seasons: $vm->seasons,
                post_id: $this->extract_post_id( $vm ),
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

            $error_vars = array_merge(
                $default_vars,
                [
                    'msg'   => $msg,
                    'class' => self::ALERT_CLASS_DANGER,
                ]
            );
            return $this->renderer->render_to_string( self::ALERT_TEMPLATE, [], $error_vars );
        } catch ( Throwable $e ) {
            $this->restore_buffer_level( $level );
            throw $e;
        }
    }

    /**
     * Retrieves a post ID from the supplied view model or the current WordPress post context.
     *
     * @param object $vm View model that may contain a non-empty post_id property.
     *
     * @return string|int Post ID from the view model, current WordPress post ID, or an empty string when unavailable.
     */
    private function extract_post_id( object $vm ): string|int {
        if ( isset( $vm->post_id ) && '' !== $vm->post_id ) {
            return $vm->post_id;
        }

        if ( function_exists( 'get_the_ID' ) ) {
            $current_id = get_the_ID();
            if ( false !== $current_id ) {
                return $current_id;
            }
        }

        return '';
    }

    /**
     * Restore the output buffering stack to the specified level.
     *
     * @param int $level output buffering level to restore.
     *
     * @return void
     */
    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }

    /**
     * Resolves the icon asset path for a header based on competition type.
     *
     * @param string $type Competition type.
     *
     * @return string|null Icon asset path, or null when unknown.
     */
    private function resolve_header_icon_path( string $type ): ?string {
        return match ( $type ) {
            self::TYPE_LEAGUE     => self::ICON_LEAGUE,
            self::TYPE_CUP        => self::ICON_CUP,
            self::TYPE_TOURNAMENT => self::ICON_TOURNAMENT,
            default               => null,
        };
    }

    /**
     * Formats start and end dates as display strings and determines if a valid range exists.
     *
     * @param mixed  $date_start  Start date (string, DateTimeInterface, int, or null).
     * @param mixed  $date_end    End date (string, DateTimeInterface, int, or null).
     * @param string $date_format Date format string.
     *
     * @return array Array containing [has_date_range, date_start_display, date_end_display].
     */
    private function format_date_range( mixed $date_start, mixed $date_end, string $date_format ): array {
        $range = Date_Range::try_create(
            $this->to_date_range_arg( $date_start ),
            $this->to_date_range_arg( $date_end )
        );
        if ( $range ) {
            return [
                true,
                (string) mysql2date( $date_format, $range->get_start_string() ),
                (string) mysql2date( $date_format, $range->get_end_string() ),
            ];
        }

        $has_date_range = ! empty( $date_start ) && ! empty( $date_end );

        return [
            $has_date_range,
            $has_date_range ? (string) mysql2date( $date_format, $this->to_date_string( $date_start ) ) : null,
            $has_date_range ? (string) mysql2date( $date_format, $this->to_date_string( $date_end ) ) : null,
        ];
    }

    /**
     * Normalises a raw date value into an accepted Date_Range factory argument.
     *
     * @param mixed $date Date input.
     *
     * @return DateTimeInterface|string|null
     */
    private function to_date_range_arg( mixed $date ): DateTimeInterface|string|null {
        if ( $date instanceof DateTimeInterface || is_string( $date ) || null === $date ) {
            return $date;
        }

        return is_scalar( $date ) ? (string) $date : null;
    }

    /**
     * Converts a raw date value into a string representation for mysql2date formatting.
     *
     * @param mixed $date Date input.
     *
     * @return string
     */
    private function to_date_string( mixed $date ): string {
        if ( $date instanceof DateTimeInterface ) {
            return $date->format( 'Y-m-d H:i:s' );
        }

        return is_scalar( $date ) ? (string) $date : '';
    }

    /**
     * Build the display header view model for a competition.
     *
     * @param object               $competition competition.
     * @param array<string, mixed> $competition_season competition season data.
     * @param string|null          $season selected season.
     * @param array<int, mixed>    $seasons available seasons.
     * @param string|int           $post_id post id.
     * @param string               $pagename pagename.
     * @param string               $date_format date format.
     *
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
        $raw_type  = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? self::TYPE_LEAGUE );
        $type_str  = $raw_type instanceof BackedEnum ? $raw_type->value : (string) $raw_type;
        $icon_path = $this->resolve_header_icon_path( $type_str );

        $date_format = $this->resolve_date_format( $date_format );

        $date_start = $competition_season['date_start'] ?? null;
        $date_end   = $competition_season['date_end'] ?? null;
        [ $has_date_range, $date_start_display, $date_end_display ] = $this->format_date_range( $date_start, $date_end, $date_format );

        $form_action = ( '' !== (string) $post_id ) ? (string) get_permalink( (int) $post_id ) : '';

        return new Competition_Header_View_Model(
            name: (string) ( $competition->name ?? '' ),
            season_title: empty( $season ) ? null : $season,
            icon_path: $icon_path,
            venue_name: ! empty( $competition_season['venue_name'] ) ? $competition_season['venue_name'] : null,
            date_start_display: $date_start_display,
            date_end_display: $date_end_display,
            has_date_range: $has_date_range,
            entry_link: ! empty( $competition->entry_link ) ? (string) $competition->entry_link : null,
            show_season_selector: empty( $season ),
            post_id: $post_id,
            pagename: $pagename,
            form_action: $form_action,
            seasons: $seasons,
            current_season_name: $competition_season['name'] ?? ''
        );
    }

    /**
     * Builds the view model used to render an event header.
     *
     * @param object      $event Event data used to derive the title, competition details, season dates, and event type.
     * @param object|null $club Optional club data used when building a club event page URL.
     * @param string|int  $curr_season Current season or round identifier.
     * @param array       $seasons Available seasons or rounds for the selector.
     * @param int         $header_level Heading level to use for the rendered event title.
     * @param string|null $standings_template Optional standings template name used to determine selector visibility.
     * @param string|int  $post_id Optional post ID used to generate the form action URL.
     * @param string      $pagename_query Page name or query path used when resolving the event URL.
     * @param string      $date_format Optional date format used for season start and end dates.
     *
     * @return Event_Header_View_Model View model containing event header display data.
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
        $curr_season_str  = (string) $curr_season;
        $raw_comp_type    = $event->competition->type ?? '';
        $competition_type = $raw_comp_type instanceof BackedEnum ? $raw_comp_type->value : (string) $raw_comp_type;

        $icon_path  = $this->resolve_header_icon_path( $competition_type );
        $event_name = (string) ( $event->name ?? '' );
        $pagename   = $this->resolve_event_header_pagename( $competition_type, $event_name, $pagename_query, $club );

        $is_box       = ! empty( $event->is_box );
        $season_label = $is_box ? __( 'Round', 'racketmanager' ) : __( 'Season', 'racketmanager' );

        $competition_name = $event->competition->name ?? null;
        $competition_link = null;
        if ( ! empty( $competition_name ) ) {
            $competition_link = '/' . Util::seo_url( (string) $competition_name ) . '/' . $curr_season_str . '/';
        }

        $date_format = $this->resolve_date_format( $date_format );

        $date_start = $event->competition->current_season['date_start'] ?? null;
        $date_end   = $event->competition->current_season['date_end'] ?? null;
        [ $has_date_range, $date_start_display, $date_end_display ] = $this->format_date_range( $date_start, $date_end, $date_format );

        $show_season_selector = empty( $standings_template ) || 'constitution' !== $standings_template;
        $form_action          = ( '' !== (string) $post_id ) ? (string) get_permalink( (int) $post_id ) : '';

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
     * Resolves the pagename route for an event header.
     *
     * @param string      $competition_type Competition type string.
     * @param string      $event_name       Event name.
     * @param string      $pagename_query   Query parameter pagename.
     * @param object|null $club             Optional club object.
     *
     * @return string Resolved pagename URL path.
     */
    private function resolve_event_header_pagename(
        string $competition_type,
        string $event_name,
        string $pagename_query,
        ?object $club
    ): string {
        if ( 'club/event' === $pagename_query && $club ) {
            $club_shortcode = (string) ( $club->shortcode ?? '' );
            return self::PATH_CLUBS . Util::seo_url( $club_shortcode ) . '/event/' . Util::seo_url( $event_name ) . '/';
        }

        if ( self::TYPE_TOURNAMENT === $competition_type ) {
            return $pagename_query;
        }

        return '/' . $competition_type . 's/' . Util::seo_url( $event_name ) . '/';
    }

    /**
     * Builds view models for a list of competitions.
     *
     * @param array       $competitions List of competition data objects or existing competition list item view models.
     * @param string      $competition_type Competition type used to determine icons, links, and entry form paths.
     * @param object|null $player Optional player data used to append a player-specific path to competition links.
     * @param string      $date_format Date format used for displaying competition start and end dates.
     *
     * @return array List of competition list item view models.
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

            $items[] = $this->present_competition_list_item( $competition, $competition_type, $player, $date_format );
        }

        return $items;
    }

    /**
     * Builds a single competition list item view model.
     *
     * @param object      $competition      Competition data object.
     * @param string      $competition_type Competition type.
     * @param object|null $player           Optional player data object.
     * @param string      $date_format      Date format string.
     *
     * @return Competition_List_Item_View_Model
     */
    private function present_competition_list_item(
        object $competition,
        string $competition_type,
        ?object $player,
        string $date_format
    ): Competition_List_Item_View_Model {
        $icon_path = match ( $competition_type ) {
            self::TYPE_LEAGUE     => self::ICON_LEAGUE,
            self::TYPE_TOURNAMENT,
            self::TYPE_CUP        => self::ICON_TOURNAMENT,
            default               => null,
        };

        [ $competition_link, $display_name ] = $this->resolve_competition_list_link_and_name( $competition, $competition_type, $player );

        $date_start = $competition->date_start ?? null;
        $date_end   = $competition->date_end ?? null;
        [ $has_date_range, $date_start_display, $date_end_display ] = $this->format_date_range( $date_start, $date_end, $date_format );

        $is_open   = ! empty( $competition->is_open );
        $entry_url = $this->resolve_competition_entry_url( $competition, $competition_type, $display_name, $is_open );

        return new Competition_List_Item_View_Model(
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

    /**
     * Resolves the competition link URL and display name for a list item.
     *
     * @param object      $competition      Competition data object.
     * @param string      $competition_type Competition type.
     * @param object|null $player           Optional player data object.
     *
     * @return array{string, string} Array containing [competition_link, display_name].
     */
    private function resolve_competition_list_link_and_name(
        object $competition,
        string $competition_type,
        ?object $player
    ): array {
        $comp_name = (string) ( $competition->name ?? '' );
        if ( self::TYPE_TOURNAMENT === $competition_type ) {
            $competition_link = '/' . self::TYPE_TOURNAMENT . '/' . Util::seo_url( $comp_name ) . '/';
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
            $player_name       = (string) ( $player->display_name ?? '' );
            $competition_link .= ltrim( self::PATH_PLAYER, '/' ) . Util::seo_url( $player_name ) . '/';
        }

        return [ $competition_link, $display_name ];
    }

    /**
     * Resolves the entry form URL for a competition if entries are open.
     *
     * @param object $competition      Competition data object.
     * @param string $competition_type Competition type.
     * @param string $display_name     Formatted display name.
     * @param bool   $is_open          Whether entries are currently open.
     *
     * @return string|null Entry form URL or null if closed.
     */
    private function resolve_competition_entry_url(
        object $competition,
        string $competition_type,
        string $display_name,
        bool $is_open
    ): ?string {
        if ( ! $is_open ) {
            return null;
        }

        $entry_url = '/' . Util::seo_url( $competition_type ) . '/entry-form/' . Util::seo_url( $display_name ) . '/';
        if ( self::TYPE_TOURNAMENT !== $competition_type && ! empty( $competition->current_season['name'] ) ) {
            $entry_url .= $competition->current_season['name'] . '/';
        }

        return $entry_url;
    }

    /**
     * Builds the view model used to render a competition overview.
     *
     * @param object $competition Competition data used to determine current timeline status.
     * @param object $overview Overview statistics and metadata for the competition.
     * @param array  $competition_season Optional season data containing venue and competition timeline dates.
     * @param string $date_format Optional date format used for timeline dates.
     *
     * @return Competition_Overview_View_Model View model containing competition overview display data.
     */
    public function present_overview(
        object $competition,
        object $overview,
        array $competition_season = [],
        string $date_format = ''
    ): Competition_Overview_View_Model {
        $date_format = $this->resolve_date_format( $date_format );

        $timeline_items = $this->build_overview_timeline_items( $competition, $competition_season, $date_format );

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
     * Builds timeline milestone items for the competition overview.
     *
     * @param object               $competition        Competition data object.
     * @param array<string, mixed> $competition_season Season data with timeline dates.
     * @param string               $date_format        Date format string.
     *
     * @return array<int, array<string, string>> Array of timeline item definitions.
     */
    private function build_overview_timeline_items(
        object $competition,
        array $competition_season,
        string $date_format
    ): array {
        if ( empty( $competition_season['date_end'] ) ) {
            return [];
        }

        $items = [];

        $open_item = $this->build_overview_timeline_item(
            'entry_open',
            __( 'Entry opens', 'racketmanager' ),
            $competition_season['date_open'] ?? null,
            $date_format,
            'is-entry-open',
            (bool) ( $competition->is_open ?? false ),
            'is-current is-success'
        );
        if ( null !== $open_item ) {
            $items[] = $open_item;
        }

        $closing_item = $this->build_overview_timeline_item(
            'closing_deadline',
            __( 'Closing deadline', 'racketmanager' ),
            $competition_season['date_closing'] ?? null,
            $date_format,
            'is-entry-closed',
            (bool) ( $competition->is_closed ?? false ),
            'is-current is-warning'
        );
        if ( null !== $closing_item ) {
            $items[] = $closing_item;
        }

        $start_item = $this->build_overview_timeline_item(
            'start_competition',
            __( 'Start competition', 'racketmanager' ),
            $competition_season['date_start'] ?? null,
            $date_format,
            'is-started',
            (bool) ( $competition->is_started ?? false ),
            'is-current is-success'
        );
        if ( null !== $start_item ) {
            $items[] = $start_item;
        }

        $is_complete = (bool) ( $competition->is_complete ?? false );
        $items[]     = [
            'key'           => 'end_competition',
            'label'         => __( 'End of competition', 'racketmanager' ),
            'date'          => (string) mysql2date( $date_format, (string) $competition_season['date_end'] ),
            'item_class'    => 'is-finished',
            'current_class' => $is_complete ? 'is-current is-danger' : '',
        ];

        return $items;
    }

    /**
     * Builds a single timeline milestone item if the milestone date is present.
     *
     * @param string $key                Milestone key identifier.
     * @param string $label              Translated milestone label.
     * @param mixed  $date               Raw date value.
     * @param string $date_format        Display date format.
     * @param string $item_class         CSS class for the item state.
     * @param bool   $is_current         Whether the milestone is currently active.
     * @param string $current_class_name CSS class applied when currently active.
     *
     * @return array<string, string>|null Item definition or null when date is empty.
     */
    private function build_overview_timeline_item(
        string $key,
        string $label,
        mixed $date,
        string $date_format,
        string $item_class,
        bool $is_current,
        string $current_class_name
    ): ?array {
        if ( empty( $date ) ) {
            return null;
        }

        return [
            'key'           => $key,
            'label'         => $label,
            'date'          => (string) mysql2date( $date_format, (string) $date ),
            'item_class'    => $item_class,
            'current_class' => $is_current ? $current_class_name : '',
        ];
    }

    /**
     * Builds the rules page URL for a competition.
     *
     * @param object $competition Competition data used to derive the rules page slug.
     *
     * @return string Rules page URL for the competition.
     */
    public function get_rules_url( object $competition ): string {
        $name = (string) ( $competition->name ?? ( method_exists( $competition, 'get_name' ) ? $competition->get_name() : '' ) );

        return self::PATH_RULES . Util::seo_url( $name ) . '-rules/';
    }

    /**
     * Gets the URL for the specified club.
     *
     * @param object $club Club data used to generate the club URL.
     *
     * @return string Club URL.
     */
    public function get_club_url( object $club ): string {
        return $this->club_presenter->get_link( $club );
    }

    /**
     * Formats a closing date for display.
     *
     * @param string|null $date_closing Closing date value to format, or null to return an empty string.
     *
     * @return string Formatted closing date, or an empty string when no closing date is provided.
     */
    public function present_closing_date( ?string $date_closing ): string {
        if ( empty( $date_closing ) ) {
            return '';
        }
        $date_format = $this->resolve_date_format();
        return (string) mysql2date( $date_format, $date_closing );
    }

    /**
     * Builds URL paths for clubs within a competition's current season.
     *
     * @param object   $competition Competition data containing the competition name and current season name.
     * @param iterable $clubs Clubs used to generate keyed club link paths.
     *
     * @return array Club link paths keyed by club ID.
     */
    public function present_competition_club_links( object $competition, iterable $clubs ): array {
        $links       = [];
        $comp_name   = (string) ( $competition->name ?? '' );
        $season_name = (string) ( $competition->current_season['name'] ?? '' );

        foreach ( $clubs as $club ) {
            $club_id           = $this->extract_club_id( $club );
            $shortcode         = $this->extract_club_shortcode_from_club( $club );
            $links[ $club_id ] = '/' . Util::seo_url( $comp_name ) . '/' . $season_name . self::PATH_CLUB . Util::seo_url( $shortcode ) . '/';
        }

        return $links;
    }

    /**
     * Builds detail page links for a competition club, including club, team, and player URLs.
     *
     * @param object $competition Competition data used to determine competition type, name, current season, and championship status.
     * @param object $competition_club Club data containing the shortcode and optional teams and players to link.
     *
     * @return array Array containing the club URL, team links keyed by team ID, and player links keyed by player ID.
     */
    public function present_competition_club_detail_links( object $competition, object $competition_club ): array {
        $comp_type       = $this->resolve_competition_type( $competition );
        $comp_name       = (string) ( $competition->name ?? '' );
        $season          = (string) ( $competition->current_season['name'] ?? '' );
        $shortcode       = (string) ( $competition_club->shortcode ?? '' );
        $is_championship = ! empty( $competition->is_championship );

        $club_url     = self::PATH_CLUBS . Util::seo_url( $shortcode ) . '/';
        $team_links   = $this->build_competition_club_team_links( $competition_club, $comp_type, $season, $is_championship );
        $player_links = $this->build_competition_club_player_links( $competition_club, $comp_name, $season );

        return [
            'club_url'     => $club_url,
            'team_links'   => $team_links,
            'player_links' => $player_links,
        ];
    }

    /**
     * Builds team links for a competition club.
     *
     * @param object $competition_club Club data containing teams.
     * @param string $comp_type        Competition type slug.
     * @param string $season           Season name.
     * @param bool   $is_championship  Whether the competition is a championship.
     *
     * @return array<int, array<string, string>> Array of team link definitions keyed by team ID.
     */
    private function build_competition_club_team_links(
        object $competition_club,
        string $comp_type,
        string $season,
        bool $is_championship
    ): array {
        if ( empty( $competition_club->teams ) || ! is_iterable( $competition_club->teams ) ) {
            return [];
        }

        $team_links = [];
        foreach ( $competition_club->teams as $team ) {
            $team_id                = self::extract_team_id( $team );
            $team_links[ $team_id ] = $this->build_competition_club_team_link_entry( $team, $comp_type, $season, $is_championship );
        }

        return $team_links;
    }

    /**
     * Builds a single team link entry for competition club details.
     *
     * @param mixed  $team            Team data object, array, or fallback value.
     * @param string $comp_type       Competition type slug.
     * @param string $season          Season name.
     * @param bool   $is_championship Whether the competition is a championship.
     *
     * @return array<string, string> Array containing team_link and league_link.
     */
    private function build_competition_club_team_link_entry(
        mixed $team,
        string $comp_type,
        string $season,
        bool $is_championship
    ): array {
        $team_name   = $this->extract_team_name( $team );
        $group_title = $this->extract_club_team_group_title( $team, $is_championship );
        $title_slug  = Util::seo_url( $group_title );
        $team_link   = '/' . $comp_type . '/' . $title_slug . '/' . $season . self::PATH_TEAM . Util::seo_url( $team_name ) . '/';
        $prefix      = $is_championship ? $comp_type . 's' : $comp_type;
        $league_link = '/' . $prefix . '/' . $title_slug . '/' . $season . '/';

        return [
            'team_link'   => $team_link,
            'league_link' => $league_link,
        ];
    }

    /**
     * Builds player links for a competition club.
     *
     * @param object $competition_club Club data containing players.
     * @param string $comp_name        Competition name.
     * @param string $season           Season name.
     *
     * @return array<int, string> Array of player URLs keyed by player ID.
     */
    private function build_competition_club_player_links(
        object $competition_club,
        string $comp_name,
        string $season
    ): array {
        if ( empty( $competition_club->players ) || ! is_iterable( $competition_club->players ) ) {
            return [];
        }

        $player_links = [];
        foreach ( $competition_club->players as $player ) {
            $player_id                  = $this->extract_player_id( $player );
            $player_name                = $this->extract_club_player_name( $player );
            $player_links[ $player_id ] = '/' . Util::seo_url( $comp_name ) . '/' . $season . self::PATH_PLAYER . Util::seo_url( $player_name ) . '/';
        }

        return $player_links;
    }

    /**
     * Builds team, club, and league links for teams in a competition.
     *
     * @param object   $competition Competition data used to resolve the competition type, name, and current season.
     * @param iterable $teams Teams to build links for, provided as objects or arrays containing team, league, and club details.
     *
     * @return array Links keyed by team ID, each containing team, club, and league URL paths.
     */
    public function present_competition_teams_links( object $competition, iterable $teams ): array {
        $comp_type = $this->resolve_competition_type( $competition );
        $comp_name = (string) ( $competition->name ?? '' );
        $season    = (string) ( $competition->current_season['name'] ?? '' );

        $links = [];
        foreach ( $teams as $team ) {
            $team_id           = $this->extract_competition_team_id( $team );
            $links[ $team_id ] = $this->build_competition_team_link_entry( $team, $comp_type, $comp_name, $season );
        }

        return $links;
    }

    /**
     * Builds link entries for a team within a competition.
     *
     * @param mixed  $team      Team data object, array, or fallback value.
     * @param string $comp_type Competition type slug.
     * @param string $comp_name Competition name.
     * @param string $season    Season name.
     *
     * @return array<string, string> Team, club, and league link paths.
     */
    private function build_competition_team_link_entry(
        mixed $team,
        string $comp_type,
        string $comp_name,
        string $season
    ): array {
        $league_name    = $this->extract_league_name_from_team( $team );
        $team_name      = $this->extract_competition_team_name( $team );
        $club_shortcode = $this->extract_club_shortcode( $team );

        return [
            'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_name ) . '/' . $season . self::PATH_TEAM . Util::seo_url( $team_name ) . '/',
            'club_link'   => '/' . Util::seo_url( $comp_name ) . '/' . $season . self::PATH_CLUB . Util::seo_url( $club_shortcode ) . '/',
            'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_name ) . '/' . $season . '/',
        ];
    }

    /**
     * Builds the base player URL for a competition using its name and current season.
     *
     * @param object $competition Competition data containing the competition name and current season name.
     *
     * @return string Base URL path for competition player pages.
     */
    public function get_competition_player_base_url( object $competition ): string {
        $comp_name = (string) ( $competition->name ?? '' );
        $season    = (string) ( $competition->current_season['name'] ?? '' );
        return '/' . Util::seo_url( $comp_name ) . '/' . $season . self::PATH_PLAYER;
    }

    /**
     * Builds URL links for the events belonging to a competition.
     *
     * @param object   $competition Competition data used to determine the competition type and current season.
     * @param iterable $events Events used to generate event-specific links, keyed by event ID.
     *
     * @return array Event links indexed by event ID.
     */
    public function present_competition_events_links( object $competition, iterable $events ): array {
        $comp_type = $this->resolve_competition_type( $competition );
        $season    = (string) ( $competition->current_season['name'] ?? '' );

        $links = [];
        foreach ( $events as $event ) {
            $event_id           = $this->extract_event_id( $event );
            $event_name         = $this->extract_event_name( $event );
            $links[ $event_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/';
        }

        return $links;
    }

    /**
     * Extracts event ID from an event object or array.
     *
     * @param mixed $event Event data object, array, or fallback value.
     *
     * @return int Event ID.
     */
    private function extract_event_id( mixed $event ): int {
        if ( is_object( $event ) ) {
            return $event->event_id ?? ( $event->id ?? 0 );
        }
        if ( is_array( $event ) ) {
            return (int) ( $event['event_id'] ?? ( $event['id'] ?? 0 ) );
        }

        return 0;
    }

    /**
     * Extracts event name from an event object or array.
     *
     * @param mixed $event Event data object, array, or fallback value.
     *
     * @return string Event name.
     */
    private function extract_event_name( mixed $event ): string {
        if ( is_object( $event ) ) {
            return $event->event_name ?? ( $event->name ?? '' );
        }
        if ( is_array( $event ) ) {
            return (string) ( $event['event_name'] ?? ( $event['name'] ?? '' ) );
        }

        return '';
    }

    /**
     * Builds player profile links for players grouped within a competition.
     *
     * @param object   $competition Competition data used to determine the base player URL.
     * @param iterable $grouped_players Iterable collection of grouped player records, where each group contains player objects or arrays.
     *
     * @return array Associative array of player IDs mapped to their competition player URLs.
     */
    public function present_competition_players_links( object $competition, iterable $grouped_players ): array {
        $base_url = $this->get_competition_player_base_url( $competition );

        return $this->generate_player_links( $grouped_players, $base_url );
    }

    /**
     * Builds the base player URL for an event using its competition type, event name, and current season.
     *
     * @param object $event Event data containing competition, name, and current season details.
     *
     * @return string Base URL for the event player route.
     */
    public function get_event_player_base_url( object $event ): string {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( $event->name ?? '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        return '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . self::PATH_PLAYER;
    }

    /**
     * Builds club-specific event links indexed by club ID.
     *
     * @param object   $event Event data used to resolve the competition type, event name, and current season.
     * @param iterable $clubs Club records used to build URL paths, each provided as an object or array containing an ID and shortcode.
     *
     * @return array Array of event club links keyed by club ID.
     */
    public function present_event_club_links( object $event, iterable $clubs ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( $event->name ?? '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );

        $links = [];
        foreach ( $clubs as $club ) {
            $club_id           = $this->extract_club_id( $club );
            $shortcode         = $this->extract_club_shortcode_from_club( $club );
            $links[ $club_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . self::PATH_CLUB . Util::seo_url( $shortcode ) . '/';
        }

        return $links;
    }

    /**
     * Extracts club ID from a club object or array.
     *
     * @param mixed $club Club data object, array, or fallback value.
     *
     * @return int Club ID.
     */
    private function extract_club_id( mixed $club ): int {
        if ( is_object( $club ) ) {
            return $club->id ?? 0;
        }
        if ( is_array( $club ) ) {
            return (int) ( $club['id'] ?? 0 );
        }

        return 0;
    }

    /**
     * Extracts club shortcode from a club object or array.
     *
     * @param mixed $club Club data object, array, or fallback value.
     *
     * @return string Club shortcode.
     */
    private function extract_club_shortcode_from_club( mixed $club ): string {
        if ( is_object( $club ) ) {
            return $club->shortcode ?? '';
        }
        if ( is_array( $club ) ) {
            return (string) ( $club['shortcode'] ?? '' );
        }

        return '';
    }

    /**
     * Builds detail links for a club within an event, including club, team, and player URLs.
     *
     * @param object $event Event data used to determine the competition type, event name, and current season.
     * @param object $event_club Club data containing the shortcode and optional team and player collections.
     *
     * @return array Array containing the club URL, team links keyed by team ID, and player links keyed by player ID.
     */
    public function present_event_club_detail_links( object $event, object $event_club ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( $event->name ?? '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        $shortcode  = (string) ( $event_club->shortcode ?? '' );

        $club_url     = self::PATH_CLUBS . Util::seo_url( $shortcode ) . '/';
        $team_links   = $this->build_event_club_team_links( $event_club, $comp_type, $season );
        $player_links = $this->build_event_club_player_links( $event_club, $comp_type, $event_name, $season );

        return [
            'club_url'     => $club_url,
            'team_links'   => $team_links,
            'player_links' => $player_links,
        ];
    }

    /**
     * Builds team links for an event club.
     *
     * @param object $event_club Club data containing teams.
     * @param string $comp_type  Competition type slug.
     * @param string $season     Season name.
     *
     * @return array<int, array<string, string>> Array of team link definitions keyed by team ID.
     */
    private function build_event_club_team_links(
        object $event_club,
        string $comp_type,
        string $season
    ): array {
        if ( empty( $event_club->teams ) || ! is_iterable( $event_club->teams ) ) {
            return [];
        }

        $team_links = [];
        foreach ( $event_club->teams as $team ) {
            $team_id      = self::extract_team_id( $team );
            $team_name    = $this->extract_team_name( $team );
            $league_title = $this->extract_league_name_from_team( $team );

            $team_links[ $team_id ] = [
                'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . self::PATH_TEAM . Util::seo_url( $team_name ) . '/',
                'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/',
            ];
        }

        return $team_links;
    }

    /**
     * Builds player links for an event club.
     *
     * @param object $event_club Club data containing players.
     * @param string $comp_type  Competition type slug.
     * @param string $event_name Event name.
     * @param string $season     Season name.
     *
     * @return array<int, string> Array of player URLs keyed by player ID.
     */
    private function build_event_club_player_links(
        object $event_club,
        string $comp_type,
        string $event_name,
        string $season
    ): array {
        if ( empty( $event_club->players ) || ! is_iterable( $event_club->players ) ) {
            return [];
        }

        $player_links = [];
        foreach ( $event_club->players as $player ) {
            $player_id                  = $this->extract_player_id( $player );
            $player_name                = $this->extract_club_player_name( $player );
            $player_links[ $player_id ] = '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . self::PATH_PLAYER . Util::seo_url( $player_name ) . '/';
        }

        return $player_links;
    }

    /**
     * Builds team, club, and league links for teams in an event.
     *
     * @param object   $event Event data used to determine the competition type, event name, current season, and box league status.
     * @param iterable $teams Teams used to generate links, where each item may be an object or array containing team, league, and club details.
     *
     * @return array Array of link data keyed by team ID, containing team, club, and league URLs.
     */
    public function present_event_teams_links( object $event, iterable $teams ): array {
        $comp_type  = $this->resolve_competition_type( $event->competition ?? null );
        $event_name = (string) ( $event->name ?? '' );
        $season     = (string) ( $event->current_season['name'] ?? '' );
        $is_box     = ! empty( $event->is_box );

        $links = [];
        foreach ( $teams as $team ) {
            $team_id           = self::extract_team_id( $team );
            $links[ $team_id ] = $this->build_event_team_link_entry( $team, $comp_type, $event_name, $season, $is_box );
        }

        return $links;
    }

    /**
     * Builds link entries for a team within an event.
     *
     * @param mixed  $team       Team data object, array, or fallback value.
     * @param string $comp_type  Competition type slug.
     * @param string $event_name Event name.
     * @param string $season     Season name.
     * @param bool   $is_box     Whether the event is a box league.
     *
     * @return array<string, string> Team, club, and league link paths.
     */
    private function build_event_team_link_entry(
        mixed $team,
        string $comp_type,
        string $event_name,
        string $season,
        bool $is_box
    ): array {
        $team_name      = $this->extract_team_name( $team );
        $league_title   = $this->extract_league_name_from_team( $team );
        $club_shortcode = $this->extract_club_shortcode( $team );

        if ( $is_box ) {
            $league_link = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . __( 'round', 'racketmanager' ) . '-' . $season . '/';
        } else {
            $league_link = '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . '/';
        }

        return [
            'team_link'   => '/' . $comp_type . '/' . Util::seo_url( $league_title ) . '/' . $season . self::PATH_TEAM . Util::seo_url( $team_name ) . '/',
            'club_link'   => '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . self::PATH_CLUB . Util::seo_url( $club_shortcode ) . '/',
            'league_link' => $league_link,
        ];
    }

    /**
     * Extract the league title from a team object or array.
     *
     * @param mixed $team Team data containing a league title.
     *
     * @return string League title, or an empty string when unavailable.
     */
    private function extract_league_name_from_team( mixed $team ): string {
        if ( is_object( $team ) ) {
            if ( isset( $team->league_name ) ) {
                $league_name = $team->league_name;
            } elseif ( isset( $team->league_title ) ) {
                $league_name = $team->league_title;
            } else {
                $league_name = '';
            }
            return $league_name;
        }
        if ( is_array( $team ) ) {
            if ( isset( $team['league_name'] ) ) {
                $league_name = $team['league_name'];
            } elseif ( isset( $team['league_title'] ) ) {
                $league_name = $team['league_title'];
            } else {
                $league_name = '';
            }
            return $league_name;
        }
        return '';
    }

    /**
     * Extracts a club shortcode from a team object or array.
     *
     * @param mixed $team Team data containing club object/array or club shortcode property.
     *
     * @return string Club shortcode.
     */
    private function extract_club_shortcode( mixed $team ): string {
        if ( is_object( $team ) ) {
            return is_object( $team->club ?? null ) ? $team->club->shortcode ?? '' : $team->club_shortcode ?? '';
        }
        if ( is_array( $team ) ) {
            return (string) ( is_array( $team['club'] ?? null ) ? $team['club']['shortcode'] ?? '' : $team['club_shortcode'] ?? '' );
        }

        return '';
    }

    /**
     * Extracts a team ID from an object or array, preferring team_id before falling back to id.
     *
     * @param mixed $team Team data containing team_id or id property or array key.
     *
     * @return int Team ID, or 0 when no ID is available.
     */
    public static function extract_team_id( mixed $team ): int {
        if ( is_object( $team ) ) {
            $id = ( isset( $team->team_id ) && '' !== $team->team_id ) ? $team->team_id : ( $team->id ?? 0 );
            return is_numeric( $id ) ? (int) $id : 0;
        }
        if ( is_array( $team ) ) {
            $id = ( isset( $team['team_id'] ) && '' !== $team['team_id'] ) ? $team['team_id'] : ( $team['id'] ?? 0 );
            return is_numeric( $id ) ? (int) $id : 0;
        }

        return 0;
    }

    /**
     * Extracts the team name from an object or array.
     *
     * @param mixed $team Team data containing a name property or name key.
     *
     * @return string Team name when available, or an empty string when not found.
     */
    private function extract_team_name( mixed $team ): string {
        if ( is_object( $team ) ) {
            return $team->name ?? '';
        }
        if ( is_array( $team ) ) {
            return (string) ( $team['name'] ?? '' );
        }

        return '';
    }

    /**
     * Extracts event name or league title from a team in club details based on championship status.
     *
     * @param mixed $team            Team data object, array, or fallback value.
     * @param bool  $is_championship Whether the competition is a championship.
     *
     * @return string Event name or league title.
     */
    private function extract_club_team_group_title( mixed $team, bool $is_championship ): string {
        if ( is_object( $team ) ) {
            return $is_championship ? ( $team->event_name ?? '' ) : ( $team->league_name ?? $team->league_title ?? '' );
        }
        if ( is_array( $team ) ) {
            return (string) ( $is_championship ? ( $team['event_name'] ?? '' ) : ( $team['league_name'] ?? $team['league_title'] ?? '' ) );
        }

        return '';
    }

    /**
     * Extracts a competition team ID from an object or array, preferring team_id before falling back to id.
     *
     * @param mixed $team Team data containing team_id or id.
     *
     * @return int Team ID, or 0 when no ID is available.
     */
    private function extract_competition_team_id( mixed $team ): int {
        return self::extract_team_id( $team );
    }

    /**
     * Extracts a competition team name from an object or array, preferring team_name before falling back to name.
     *
     * @param mixed $team Team data containing team_name or name.
     *
     * @return string Team name when available, or an empty string when not found.
     */
    private function extract_competition_team_name( mixed $team ): string {
        if ( is_object( $team ) ) {
            return $team->team_name ?? ( $team->name ?? '' );
        }
        if ( is_array( $team ) ) {
            return (string) ( $team['team_name'] ?? ( $team['name'] ?? '' ) );
        }

        return '';
    }

    /**
     * Builds player profile links for players grouped within an event.
     *
     * @param object   $event Event data used to determine the base player URL.
     * @param iterable $grouped_players Grouped player data containing player objects or arrays with identifiers and display names.
     *
     * @return array Player profile URLs indexed by player ID.
     */
    public function present_event_players_links( object $event, iterable $grouped_players ): array {
        $base_url = $this->get_event_player_base_url( $event );

        return $this->generate_player_links( $grouped_players, $base_url );
    }

    /**
     * Builds league URLs for an event, keyed by league ID.
     *
     * @param object      $event Event data used to determine whether league URLs should include a round prefix and to resolve the current season when none is provided.
     * @param iterable    $leagues League records, as objects or arrays, containing ID and title values used to build URL paths.
     * @param string|null $season Optional season or round name to append to each league URL.
     * @param string      $base_url Base URL prepended to each generated league URL.
     *
     * @return array Array of generated league URLs keyed by league ID.
     */
    public static function present_event_league_urls( object $event, iterable $leagues, ?string $season = null, string $base_url = '' ): array {
        $is_box      = ! empty( $event->is_box );
        $season_name = $season ?? (string) ( $event->current_season['name'] ?? '' );

        $links = [];
        foreach ( $leagues as $league ) {
            $league_id           = self::extract_league_id( $league );
            $league_title        = self::extract_league_title( $league );
            $links[ $league_id ] = self::build_event_league_url_href( $base_url, $league_title, $season_name, $is_box );
        }

        return $links;
    }

    /**
     * Extracts league ID from a league object or array.
     *
     * @param mixed $league League object, array, or fallback value.
     *
     * @return int League ID.
     */
    private static function extract_league_id( mixed $league ): int {
        if ( is_object( $league ) ) {
            return $league->id ?? 0;
        }
        if ( is_array( $league ) ) {
            return (int) ( $league['id'] ?? 0 );
        }

        return 0;
    }

    /**
     * Extracts league title from a league object or array.
     *
     * @param mixed $league League object, array, or fallback value.
     *
     * @return string League title.
     */
    private static function extract_league_title( mixed $league ): string {
        if ( is_object( $league ) ) {
            return method_exists( $league, 'get_name' ) ? $league->get_name() : ( $league->name ?? $league->title ?? '' );
        }
        if ( is_array( $league ) ) {
            return (string) ( $league['name'] ?? $league['title'] ?? '' );
        }

        return '';
    }

    /**
     * Builds the full URL href for an event league.
     *
     * @param string $base_url     Base URL prepended to the path.
     * @param string $league_title League title.
     * @param string $season_name  Season or round name.
     * @param bool   $is_box       Whether the event is a box league.
     *
     * @return string Full URL for the league.
     */
    private static function build_event_league_url_href(
        string $base_url,
        string $league_title,
        string $season_name,
        bool $is_box
    ): string {
        $href = $base_url . '/' . __( 'league', 'racketmanager' ) . '/' . Util::seo_url( $league_title ) . '/';
        if ( $is_box ) {
            $href .= __( 'round', 'racketmanager' ) . '-';
        }
        $href .= $season_name . '/';

        return $href;
    }

    /**
     * Builds standings link data for the leagues associated with an event.
     *
     * @param object   $event Event data used to generate standings links.
     * @param iterable $leagues Leagues for which standings URLs should be prepared.
     *
     * @return array Standings link data keyed or ordered according to the generated league URLs.
     */
    public function present_event_standings_links( object $event, iterable $leagues ): array {
        return self::present_event_league_urls( $event, $leagues );
    }

    /**
     * Builds event and league links for grouped player matches.
     *
     * @param iterable $matches_groups Match groups keyed by group identifier, where each group contains league data as an object property or array value.
     *
     * @return array Links keyed by match group identifier, each containing event and league URLs.
     */
    public function present_player_match_group_links( iterable $matches_groups ): array {
        $links = [];
        foreach ( $matches_groups as $key => $player_matches ) {
            $league = is_object( $player_matches ) ? ( $player_matches->league ?? null ) : ( $player_matches['league'] ?? null );
            if ( $league && isset( $league->event ) ) {
                $league_comp = $league->event->competition ?? null;
                $comp_type   = $this->resolve_competition_type( $league_comp );
                $event_name  = $league->event->name ?? '';
                $league_name = ( is_object( $league ) && method_exists( $league, 'get_name' ) ) ? $league->get_name() : ( $league->name ?? $league->title ?? '' );
                $season      = $league->season ?? '';

                $links[ (string) $key ] = [
                    'event_link'  => '/' . $comp_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/',
                    'league_link' => '/' . $comp_type . '/' . Util::seo_url( $league_name ) . '/' . $season . '/',
                ];
            }
        }

        return $links;
    }

    /**
     * Resolves the competition type from a competition object, scalar value, enum, or fallback value.
     *
     * @param mixed $competition Competition source used to determine the type.
     *
     * @return string Resolved competition type, defaulting to league when no type is available.
     */
    private function resolve_competition_type( mixed $competition ): string {
        if ( is_object( $competition ) ) {
            $type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? self::TYPE_LEAGUE );
        } else {
            $type = $competition ?? self::TYPE_LEAGUE;
        }

        if ( $type instanceof BackedEnum ) {
            return (string) $type->value;
        }

        return '' !== (string) $type ? (string) $type : self::TYPE_LEAGUE;
    }

    /**
     * Prepares competition winner data for display by adding link and data attributes.
     *
     * @param array $winners Winner groups keyed by group identifier, with each winner containing competition type, event name, and season data.
     *
     * @return array Winner groups containing cloned or normalized winner objects with generated display properties.
     */
    public function present_competition_winners( array $winners ): array {
        $presented = [];
        foreach ( $winners as $key => $winner_group ) {
            $presented_group = [];
            foreach ( $winner_group as $winner ) {
                $w                 = is_object( $winner ) ? clone $winner : (object) $winner;
                $comp_type         = $w->competition_type ?? 'competition';
                $event_name        = $w->event_name ?? '';
                $season            = $w->season ?? '';
                $w->link_url       = '/' . Util::seo_url( (string) $comp_type ) . 's/' . Util::seo_url( (string) $event_name ) . '/' . Util::seo_url( (string) $season ) . '/';
                $w->link_class     = null;
                $w->data_season    = null;
                $w->data_type      = null;
                $w->data_type_id   = null;
                $w->data_type_type = null;
                $presented_group[] = $w;
            }
            $presented[ $key ] = $presented_group;
        }

        return $presented;
    }

    /**
     * Builds player profile links for a club event from player statistics.
     *
     * @param object      $event Event data used to determine the competition type, event slug, and default season name.
     * @param iterable    $player_stats Player statistic records containing player full names as object properties or array keys.
     * @param string|null $season_name Optional season name to use in generated player URLs.
     *
     * @return array Associative array of player full names keyed to their generated event player URLs.
     */
    public function present_club_event_player_links( object $event, iterable $player_stats, ?string $season_name = null ): array {
        $player_links = [];
        $base_url     = $this->build_club_event_player_base_url( $event, $season_name );

        foreach ( $player_stats as $stat ) {
            $fn = $this->extract_stat_fullname( $stat );
            if ( '' !== $fn ) {
                $player_links[ $fn ] = $base_url . Util::seo_url( $fn ) . '/';
            }
        }

        return $player_links;
    }

    /**
     * Builds the base player URL for a club event view.
     *
     * @param object      $event       Event data containing competition, name, and current season details.
     * @param string|null $season_name Optional season name to override event season name.
     *
     * @return string Base player route URL.
     */
    private function build_club_event_player_base_url( object $event, ?string $season_name ): string {
        $comp_type        = ( isset( $event->competition->type ) ) ? $event->competition->type : self::TYPE_LEAGUE;
        $competition_type = $comp_type instanceof BackedEnum ? $comp_type->value : (string) $comp_type;
        $event_slug       = Util::seo_url( (string) ( $event->name ?? '' ) );
        $season_slug      = (string) ( $season_name ?? ( $event->current_season['name'] ?? '' ) );

        return '/' . $competition_type . 's/' . $event_slug . '/' . $season_slug . self::PATH_PLAYER;
    }

    /**
     * Extracts player full name from a player stat object or array.
     *
     * @param mixed $stat Player stat record.
     *
     * @return string Player full name.
     */
    private function extract_stat_fullname( mixed $stat ): string {
        if ( is_object( $stat ) ) {
            return $stat->fullname ?? '';
        }
        if ( is_array( $stat ) ) {
            return (string) ( $stat['fullname'] ?? '' );
        }

        return '';
    }

    /**
     * Builds a presented copy of an event with draw data prepared for rendering.
     *
     * @param object $event Event data containing leagues and optional finals to present.
     *
     * @return object Cloned event object with league finals transformed into presented draw data.
     */
    public function present_event_draw( object $event ): object {
        $presented_event = clone $event;
        if ( ! empty( $presented_event->leagues ) && is_array( $presented_event->leagues ) ) {
            $presented_leagues = array_map(
                function ( $league ) use ( $presented_event ) {
                    return $this->present_event_league_draw( $league, $presented_event );
                },
                $presented_event->leagues
            );
            if ( $presented_event instanceof Event ) {
                $presented_event->set_leagues( $presented_leagues );
            } else {
                $presented_event->leagues = $presented_leagues;
            }
        }

        return $presented_event;
    }

    /**
     * Prepares draw data and finals for an individual league within an event.
     *
     * @param object|array $league          League data object or array.
     * @param object       $presented_event Presented event context.
     *
     * @return object Cloned or normalized league object with presented finals if applicable.
     */
    private function present_event_league_draw( object|array $league, object $presented_event ): object {
        $league_obj = is_object( $league ) ? clone $league : (object) $league;
        if ( ! empty( $league_obj->finals ) && is_array( $league_obj->finals ) ) {
            $competition      = ( $league_obj->event->competition ?? null ) ?? ( $presented_event->competition ?? null );
            $presented_finals = Draw_Presenter::present_finals(
                $league_obj->finals,
                null,
                $league_obj,
                $competition,
                $presented_event
            );
            if ( $league_obj instanceof League ) {
                $league_obj->set_finals_list( $presented_finals );
            } else {
                $league_obj->finals = $presented_finals;
            }
        }

        return $league_obj;
    }

    /**
     * Builds lookup URLs for clubs used by the entry form.
     *
     * @param array $clubs Club records as objects or arrays containing an ID and shortcode.
     *
     * @return array Map of club IDs and shortcodes to SEO-friendly club URL fragments.
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

    /**
     * Resolve effective date format using option gateway or fallback default.
     *
     * @param string $date_format Explicit date format if provided.
     *
     * @return string
     */
    private function resolve_date_format( string $date_format = '' ): string {
        if ( '' !== $date_format ) {
            return $date_format;
        }

        $format = $this->option_gateway?->get_option( 'date_format', self::DEFAULT_DATE_FORMAT );

        return is_string( $format ) && '' !== $format ? $format : self::DEFAULT_DATE_FORMAT;
    }

    /**
     * Generates SEO-friendly player profile links from grouped player data.
     *
     * @param iterable $grouped_players Iterable collection of player groups containing player objects or arrays.
     * @param string   $base_url Base URL prepended to each generated player path.
     *
     * @return array Player links keyed by player ID.
     */
    private function generate_player_links( iterable $grouped_players, string $base_url ): array {
        $links = [];
        foreach ( $grouped_players as $players ) {
            if ( is_iterable( $players ) ) {
                foreach ( $players as $player ) {
                    $player_id           = $this->extract_player_id( $player );
                    $player_name         = $this->extract_grouped_player_name( $player );
                    $links[ $player_id ] = $base_url . Util::seo_url( $player_name ) . '/';
                }
            }
        }

        return $links;
    }

    /**
     * Extract player ID from an object or array representation.
     *
     * @param mixed $player Player object, array, or fallback value.
     *
     * @return int Player ID, or 0 when unavailable.
     */
    private function extract_player_id( mixed $player ): int {
        if ( is_object( $player ) ) {
            return $player->id ?? ( $player->ID ?? 0 );
        }
        if ( is_array( $player ) ) {
            return (int) ( $player['id'] ?? ( $player['ID'] ?? 0 ) );
        }

        return 0;
    }

    /**
     * Extracts the player name for club detail links, preferring fullname over display_name.
     *
     * @param mixed $player Player object, array, or fallback value.
     *
     * @return string Player name.
     */
    private function extract_club_player_name( mixed $player ): string {
        if ( is_object( $player ) ) {
            return (string) ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->display_name ?? '' ) );
        }
        if ( is_array( $player ) ) {
            return (string) ( $player['fullname'] ?? ( $player['display_name'] ?? '' ) );
        }

        return '';
    }

    /**
     * Extracts the player name for grouped player links, preferring display_name over fullname.
     *
     * @param mixed $player Player object, array, or fallback value.
     *
     * @return string Player name.
     */
    private function extract_grouped_player_name( mixed $player ): string {
        if ( is_object( $player ) ) {
            return (string) ( $player->display_name ?? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : '' ) );
        }
        if ( is_array( $player ) ) {
            return (string) ( $player['display_name'] ?? ( $player['fullname'] ?? '' ) );
        }

        return '';
    }
}
