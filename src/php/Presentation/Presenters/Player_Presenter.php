<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Closure;
use LogicException;
use Racketmanager\Domain\Player\Player_Ranking;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Player_Presenter_Interface;
use Racketmanager\Presentation\View_Models\Club_Link_View_Model;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Player_View_Model;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function mysql2date;
use function str_starts_with;

/**
 * Presenter for Player domain objects and templates.
 */
readonly class Player_Presenter implements Player_Presenter_Interface {

    /**
     * Constructor.
     *
     * @param Competition_Shortcode_Presenter|Closure|null $competition_presenter Competition shortcode presenter or closure.
     * @param Option_Gateway_Interface|null                $option_gateway        Option gateway.
     */
    public function __construct(
        private Competition_Shortcode_Presenter|Closure|null $competition_presenter = null,
        private ?Option_Gateway_Interface $option_gateway = null
    ) {}

    /**
     * Present full immutable Player view model for player profile and details.
     *
     * @param object     $player player object.
     * @param array      $clubs clubs array.
     * @param array      $titles titles array.
     * @param array      $player_competitions player competitions array.
     * @param bool       $is_page_header page header.
     * @param mixed|null $header_level header level.
     * @param bool       $user_can_update can user update.
     * @param string     $template template.
     *
     * @return Player_View_Model
     * @throws LogicException Logic exception.
     */
    public function present_player(
        object $player,
        array $clubs = [],
        array $titles = [],
        array $player_competitions = [],
        bool $is_page_header = false,
        mixed $header_level = null,
        bool $user_can_update = false,
        string $template = 'player'
    ): Player_View_Model {
        $id           = $this->extract_player_id( $player );
        $firstname    = $this->nullable_string( $player, 'firstname' );
        $surname      = $this->nullable_string( $player, 'surname' );
        $display_name = $this->resolve_display_name( $player, $firstname, $surname );
        $btm          = $this->nullable_string( $player, 'btm' );
        $gender       = $this->nullable_string( $player, 'gender' );
        $yob          = $this->resolve_year_of_birth( $player );
        $email        = $this->nullable_string( $player, 'email' );
        $contactno    = $this->nullable_string( $player, 'contactno' );
        $reg_date     = $this->nullable_string( $player, 'registration_date' );
        $reg_user     = $this->nullable_string( $player, 'registered_by_user' );
        $link         = $this->nullable_string( $player, 'link' ) ?? $this->get_player_link( $display_name );
        $stats        = $this->resolve_player_stats( $player );

        $presented_clubs        = $this->present_clubs( $clubs, $player );
        $presented_competitions = $this->present_competitions_group( $player_competitions, $player );

        return new Player_View_Model(
            template: $template,
            id: $id,
            firstname: $firstname,
            surname: $surname,
            display_name: $display_name,
            btm: $btm,
            gender: $gender,
            year_of_birth: $yob,
            email: $email,
            contactno: $contactno,
            registration_date: $reg_date,
            registered_by_user: $reg_user,
            link: $link,
            initials: $this->get_initials( $firstname, $surname ),
            header_level: $this->get_heading_level( $is_page_header, $header_level ),
            is_page_header: $is_page_header,
            wtn_badges: $this->get_wtn_badges( $player->wtn ?? [] ),
            win_loss_summary: $this->get_win_loss_summary( $player->statistics ?? $stats ),
            clubs: $presented_clubs,
            titles: $this->present_titles( $titles ),
            player_competitions: $presented_competitions,
            stats: $stats,
            user_can_update: $user_can_update,
            player: $player
        );
    }

    /**
     * Present player titles with precalculated URLs.
     *
     * @param array $titles titles array.
     * @return array
     */
    public function present_titles( array $titles ): array {
        if ( empty( $titles ) ) {
            return [];
        }

        $presented = [];
        foreach ( $titles as $season => $tournaments ) {
            if ( ! is_array( $tournaments ) ) {
                $presented[ $season ] = $tournaments;
                continue;
            }

            $presented[ $season ] = [];
            foreach ( $tournaments as $tournament => $matches ) {
                $presented[ $season ][ $tournament ] = [];
                if ( is_iterable( $matches ) ) {
                    $presented[ $season ][ $tournament ] = $this->present_tournament_matches( (string) $tournament, $matches );
                }
            }
        }

        return $presented;
    }

    /**
     * Present player search and directory view model.
     *
     * @param string      $search_string search string.
     * @param string|null $search_results search results.
     * @param array       $favourites favourites array.
     * @param string      $template template.
     *
     * @return Player_View_Model
     */
    public function present_players_directory(
        string $search_string = '',
        ?string $search_results = null,
        array $favourites = [],
        string $template = 'players'
    ): Player_View_Model {
        return new Player_View_Model(
            template: $template,
            search_string: $search_string,
            search_results: $search_results,
            favourites: $favourites
        );
    }

    /**
     * Present grouped players list with alphabet navigation.
     *
     * @param array  $players_list players list array.
     * @param array  $active_keys active keys array.
     * @param string $template template.
     *
     * @return Player_View_Model
     */
    public function present_players_list(
        array $players_list,
        array $active_keys = [],
        string $template = 'players-list'
    ): Player_View_Model {
        $keys = ! empty( $active_keys ) ? $active_keys : array_keys( $players_list );

        return new Player_View_Model(
            template: $template,
            alphabet_nav: $this->get_alphabet_navigation( $keys ),
            players_list: $players_list
        );
    }

    /**
     * Get player initials (e.g. "JD" for John Doe).
     *
     * @param string|null $firstname firstname.
     * @param string|null $surname surname.
     *
     * @return string
     */
    public function get_initials( ?string $firstname, ?string $surname ): string {
        $first_initial = ! empty( $firstname ) ? mb_substr( trim( $firstname ), 0, 1 ) : '';
        $last_initial  = ! empty( $surname ) ? mb_substr( trim( $surname ), 0, 1 ) : '';

        return mb_strtoupper( $first_initial . $last_initial );
    }

    /**
     * Get heading level for player header.
     *
     * @param bool       $is_page_header page header.
     * @param mixed|null $header_level header level.
     *
     * @return int
     */
    public function get_heading_level( bool $is_page_header = false, mixed $header_level = null ): int {
        if ( ! empty( $header_level ) ) {
            return (int) $header_level;
        }

        return $is_page_header ? 1 : 3;
    }

    /**
     * Format WTN ratings into badge presentation structures.
     *
     * @param mixed $wtn wtn.
     *
     * @return array
     * @throws LogicException Logic exception.
     */
    public function get_wtn_badges( mixed $wtn ): array {
        if ( empty( $wtn ) || ! is_array( $wtn ) ) {
            return array();
        }

        $help_text   = function_exists( '__' ) ? __( 'World Tennis Number for', 'racketmanager' ) : 'World Tennis Number for';
        $match_types = class_exists( Util_Lookup::class ) ? Util_Lookup::get_match_types() : array(
            'S' => 'Singles',
            'D' => 'Doubles',
        );

        $badges = array();
        foreach ( $match_types as $match_type => $description ) {
            $val = $wtn[ $match_type ]
                ?? $wtn[ strtolower( $match_type ) ]
                ?? $wtn[ strtolower( $description ) ]
                ?? $wtn[ $description ]
                ?? null;

            if ( null !== $val ) {
                $ranking = Player_Ranking::from_raw( $val, $match_type );

                $badges[] = array(
                    'type'        => $match_type,
                    'description' => $description,
                    'value'       => $ranking->get_raw_value(),
                    'tooltip'     => trim( $help_text . ' ' . $description ),
                );
            }
        }

        return $badges;
    }

    /**
     * Format win/loss summary statistics into presentation structures.
     *
     * @param mixed $statistics statistics.
     *
     * @return array|null
     * @throws LogicException Logic exception.
     */
    public function get_win_loss_summary( mixed $statistics ): ?array {
        $stats_array = is_array( $statistics ) || is_object( $statistics ) ? (array) $statistics : [];
        $total       = (object) ( $stats_array['total'] ?? [] );
        $played      = (int) ( $total->played ?? 0 );

        if ( empty( $stats_array['total'] ) || $played <= 0 ) {
            return null;
        }

        $won      = (int) ( $total->matches_won ?? 0 );
        $lost     = (int) ( $total->matches_lost ?? 0 );
        $win_pct  = $total->win_pct ?? round( ( $won / $played ) * 100, 1 );
        $won_text = function_exists( '__' ) ? __( 'won', 'racketmanager' ) : 'won';

        return array(
            'played'         => $played,
            'matches_won'    => $won,
            'matches_lost'   => $lost,
            'win_pct'        => $win_pct,
            'record_text'    => sprintf( '%d-%d (%d)', $won, $lost, $played ),
            'progress_title' => sprintf( '%s %s%%', $win_pct, $won_text ),
        );
    }

    /**
     * Generate canonical player profile link.
     *
     * @param string      $player_name player name.
     * @param string|null $club_shortcode club shortcode.
     * @param string|null $base_url base url.
     *
     * @return string
     * @throws LogicException Logic exception.
     */
    public function get_player_link( string $player_name, ?string $club_shortcode = null, ?string $base_url = null ): string {
        $slug = Seo_Slug::encode( $player_name );

        if ( ! empty( $base_url ) ) {
            return rtrim( $base_url, '/' ) . '/' . $slug . '/';
        }

        if ( ! empty( $club_shortcode ) ) {
            $club_slug = Seo_Slug::encode( $club_shortcode );
            return '/clubs/' . $club_slug . '/players/' . $slug . '/';
        }

        return '/players/' . $slug . '/';
    }

    /**
     * Generate canonical club page link from a club name or shortcode.
     *
     * @param string $club_name club name.
     *
     * @return string
     */
    public function get_club_link( string $club_name ): string {
        if ( '' === $club_name ) {
            return '';
        }

        return '/clubs/' . Seo_Slug::encode( $club_name ) . '/';
    }

    /**
     * Generate alphabet navigation array.
     *
     * @param array $active_keys active keys.
     *
     * @return array
     */
    public function get_alphabet_navigation( array $active_keys ): array {
        $navigation = array();
        foreach ( range( 'A', 'Z' ) as $char ) {
            $is_active    = in_array( $char, $active_keys, true );
            $navigation[] = array(
                'letter'    => $char,
                'is_active' => $is_active,
                'class'     => $is_active ? null : 'is-disabled',
            );
        }

        return $navigation;
    }

    /**
     * Format a date string using the configured date format.
     *
     * @param string|null $date date.
     *
     * @return string
     * @throws LogicException Logic exception.
     */
    public function format_date( ?string $date ): string {
        if ( empty( $date ) || str_starts_with( $date, '0000-00-00' ) ) {
            return '';
        }

        $date_format = $this->option_gateway?->get_option( 'date_format', 'd/m/Y' );
        if ( empty( $date_format ) || ! is_string( $date_format ) ) {
            $date_format = 'd/m/Y';
        }

        return (string) mysql2date( $date_format, $date );
    }

    /**
     * Present a single club membership item with preformatted display dates.
     *
     * @param object|array $club_player club player.
     *
     * @return Club_Membership_View_Model
     * @throws LogicException Logic exception.
     */
    public function present_club_membership( object|array $club_player ): Club_Membership_View_Model {
        $club_name = $this->extract_property( $club_player, 'club_name' )
            ?? $this->extract_property( $club_player, 'shortcode' )
            ?? $this->extract_property( $club_player, 'name' )
            ?? '';

        $club_display_name = $this->extract_property( $club_player, 'club_display_name' )
            ?? $this->extract_property( $club_player, 'name' )
            ?? $this->extract_property( $club_player, 'shortcode' )
            ?? $club_name;

        $approval_date_str     = $this->truthy_string( $this->extract_property( $club_player, 'approval_date' ) );
        $removal_date_str      = $this->truthy_string( $this->extract_property( $club_player, 'removal_date' ) );
        $registration_date_str = $this->truthy_string( $this->extract_property( $club_player, 'registration_date' ) );

        return new Club_Membership_View_Model(
            club_name: (string) $club_name,
            club_display_name: (string) $club_display_name,
            approval_date: $approval_date_str,
            approval_date_display: $this->format_date( $approval_date_str ),
            removal_date: $removal_date_str,
            removal_date_display: $this->format_date( $removal_date_str ),
            registration_date: $registration_date_str,
            registration_date_display: $this->format_date( $registration_date_str ),
            club_id: $this->nullable_int( $this->extract_property( $club_player, 'club_id' ) ),
            user_id: $this->nullable_int( $this->extract_property( $club_player, 'user_id' ) ),
            registration_id: $this->nullable_int( $this->extract_property( $club_player, 'registration_id' ) ),
            approved_by_user_id: $this->nullable_int( $this->extract_property( $club_player, 'approved_by_user_id' ) ),
            registered_by_user_id: $this->nullable_int( $this->extract_property( $club_player, 'registered_by_user_id' ) ),
            removed_by_user_id: $this->nullable_int( $this->extract_property( $club_player, 'removed_by_user_id' ) ),
            system_record: $this->nullable_bool( $this->extract_property( $club_player, 'system_record' ) ),
            club_url: $this->get_club_link( (string) $club_name ),
        );
    }

    /**
     * Build per-player link mappings (player_link, team_link) for league player rows.
     *
     * @param object                          $league  League object.
     * @param array<int|string, object|array> $players List of players as objects or arrays.
     *
     * @return array<int, array{player_link: string, team_link: string}>
     * @throws LogicException Logic exception.
     */
    public function present_league_player_links( object $league, array $players ): array {
        $base_url = $this->build_league_base_url( $league );
        $links    = [];

        foreach ( $players as $player ) {
            $player_id           = $this->extract_player_id( $player );
            $links[ $player_id ] = $this->present_single_player_links( $base_url, $player );
        }

        return $links;
    }

    /**
     * Resolve competition type string from a league object.
     *
     * @param object $league League object.
     *
     * @return string
     */
    private function resolve_competition_type( object $league ): string {
        $type = $league->event->competition->type ?? null;
        if ( $type instanceof BackedEnum ) {
            return (string) $type->value;
        }

        return (string) ( $type ?? 'league' );
    }

    /**
     * Build canonical base URL for league player and team links.
     *
     * @param object $league League object.
     *
     * @return string
     */
    private function build_league_base_url( object $league ): string {
        $competition_type = $this->resolve_competition_type( $league );
        $league_name      = (string) ( ( is_object( $league ) && method_exists( $league, 'get_name' ) ) ? $league->get_name() : ( $league->name ?? $league->title ?? '' ) );
        $season_name      = (string) ( $league->current_season['name'] ?? '' );

        return '/' . Util::seo_url( $competition_type ) . '/' . Util::seo_url( $league_name ) . '/' . $season_name . '/';
    }

    /**
     * Extract player ID from an object or array representation.
     *
     * @param object|array $player Player object or array.
     *
     * @return int
     */
    private function extract_player_id( object|array $player ): int {
        if ( is_array( $player ) ) {
            return (int) ( $player['id'] ?? 0 );
        }

        $id = $player->id ?? ( $player->ID ?? null );
        if ( null === $id && method_exists( $player, 'get_id' ) ) {
            $id = $player->get_id();
        }

        return (int) ( $id ?? 0 );
    }

    /**
     * Extract player name from an object or array representation.
     *
     * @param object|array $player Player object or array.
     *
     * @return string
     */
    private function extract_player_name( object|array $player ): string {
        if ( is_array( $player ) ) {
            return (string) ( $player['fullname'] ?? ( $player['display_name'] ?? '' ) );
        }

        if ( method_exists( $player, 'get_fullname' ) ) {
            $name = (string) $player->get_fullname();
        } elseif ( isset( $player->display_name ) ) {
            $name = (string) $player->display_name;
        } else {
            $name = trim( ( $player->firstname ?? '' ) . ' ' . ( $player->surname ?? '' ) );
        }

        return $name;
    }

    /**
     * Extract team title from an object or array representation.
     *
     * @param object|array $player Player object or array.
     *
     * @return string
     */
    private function extract_team_title( object|array $player ): string {
        if ( is_array( $player ) ) {
            return (string) ( $player['team']['title'] ?? ( $player['team']['name'] ?? '' ) );
        }

        return (string) ( $player->team->title ?? ( $player->team->name ?? '' ) );
    }

    /**
     * Present link pair for an individual player row.
     *
     * @param string       $base_url Base URL for the league season.
     * @param object|array $player   Player object or array.
     *
     * @return array{player_link: string, team_link: string}
     */
    private function present_single_player_links( string $base_url, object|array $player ): array {
        $player_name = $this->extract_player_name( $player );
        $team_title  = $this->extract_team_title( $player );

        $team_link = '';
        if ( '' !== $team_title ) {
            $team_link = $base_url . 'team/' . Util::seo_url( $team_title ) . '/';
        }

        return [
            'player_link' => $base_url . 'player/' . Util::seo_url( $player_name ) . '/',
            'team_link'   => $team_link,
        ];
    }

    /**
     * Determine raw clubs array from passed clubs or player club property.
     *
     * @param array  $clubs  Clubs array.
     * @param object $player Player object.
     *
     * @return array
     */
    private function resolve_raw_clubs( array $clubs, object $player ): array {
        if ( ! empty( $clubs ) ) {
            return $clubs;
        }

        if ( ! isset( $player->club ) ) {
            return [];
        }

        return is_array( $player->club ) ? $player->club : [ $player->club ];
    }

    /**
     * Check if a club item contains club membership specific fields.
     *
     * @param object|array $club_item Club object or array.
     *
     * @return bool
     */
    private function has_membership_fields( object|array $club_item ): bool {
        if ( is_object( $club_item ) ) {
            return isset( $club_item->registration_date )
                || isset( $club_item->approval_date )
                || isset( $club_item->removal_date )
                || isset( $club_item->registration_id );
        }

        return isset( $club_item['registration_date'] )
            || isset( $club_item['approval_date'] )
            || isset( $club_item['removal_date'] )
            || isset( $club_item['registration_id'] );
    }

    /**
     * Present a club link view model from an object or array.
     *
     * @param object|array $club_item Club object or array.
     *
     * @return Club_Link_View_Model
     */
    private function present_club_link_item( object|array $club_item ): Club_Link_View_Model {
        $club_name = $this->extract_property( $club_item, 'club_name' )
            ?? $this->extract_property( $club_item, 'shortcode' )
            ?? $this->extract_property( $club_item, 'name' )
            ?? '';

        $club_display_name = $this->extract_property( $club_item, 'club_display_name' )
            ?? $this->extract_property( $club_item, 'name' )
            ?? $this->extract_property( $club_item, 'shortcode' )
            ?? $club_name;

        $raw_club_url = $this->extract_property( $club_item, 'club_url' ) ?? '';
        $club_url     = ( '' === $raw_club_url && '' !== (string) $club_name )
            ? $this->get_club_link( (string) $club_name )
            : (string) $raw_club_url;

        return new Club_Link_View_Model(
            club_name: (string) $club_name,
            club_url: $club_url,
            club_display_name: (string) $club_display_name
        );
    }

    /**
     * Present a club object or array item into a membership or link view model.
     *
     * @param object|array $club_item Club object or array.
     *
     * @return Club_Membership_View_Model|Club_Link_View_Model
     */
    private function present_club_object_or_array( object|array $club_item ): Club_Membership_View_Model|Club_Link_View_Model {
        if ( $this->has_membership_fields( $club_item ) ) {
            return $this->present_club_membership( $club_item );
        }

        return $this->present_club_link_item( $club_item );
    }

    /**
     * Present a single club item into a membership or link view model.
     *
     * @param mixed $club_item Club item.
     *
     * @return Club_Membership_View_Model|Club_Link_View_Model|null
     */
    private function present_single_club( mixed $club_item ): Club_Membership_View_Model|Club_Link_View_Model|null {
        if ( $club_item instanceof Club_Membership_View_Model || $club_item instanceof Club_Link_View_Model ) {
            return $club_item;
        }

        if ( is_string( $club_item ) ) {
            return new Club_Link_View_Model(
                club_name: $club_item,
                club_url: $this->get_club_link( $club_item ),
                club_display_name: $club_item
            );
        }

        return is_object( $club_item ) || is_array( $club_item )
            ? $this->present_club_object_or_array( $club_item )
            : null;
    }

    /**
     * Present clubs collection into view models.
     *
     * @param array  $clubs  Clubs array.
     * @param object $player Player object.
     *
     * @return array
     */
    private function present_clubs( array $clubs, object $player ): array {
        $raw_clubs       = $this->resolve_raw_clubs( $clubs, $player );
        $presented_clubs = [];

        foreach ( $raw_clubs as $club_item ) {
            $presented = $this->present_single_club( $club_item );
            if ( null !== $presented ) {
                $presented_clubs[] = $presented;
            }
        }

        return $presented_clubs;
    }

    /**
     * Resolve the competition shortcode presenter instance, throwing if unavailable.
     *
     * @return Competition_Shortcode_Presenter
     * @throws LogicException Logic exception if competition presenter is missing.
     */
    private function resolve_competition_presenter(): Competition_Shortcode_Presenter {
        if ( null === $this->competition_presenter ) {
            throw new LogicException( 'A Competition_Shortcode_Presenter is required to present raw player competitions.' );
        }

        if ( $this->competition_presenter instanceof Closure ) {
            return ( $this->competition_presenter )();
        }

        return $this->competition_presenter;
    }

    /**
     * Present a single competition type entry.
     *
     * @param string $competition_type Competition type key.
     * @param mixed  $competitions     Competitions list.
     * @param object $player           Player object.
     *
     * @return array
     * @throws LogicException Logic exception if competition presenter is missing.
     */
    private function present_competition_entry( string $competition_type, mixed $competitions, object $player ): array {
        if ( empty( $competitions ) ) {
            return [];
        }

        $first = reset( $competitions );
        if ( $first instanceof Competition_List_Item_View_Model ) {
            return $competitions;
        }

        $competition_presenter = $this->resolve_competition_presenter();

        return $competition_presenter->present_competition_list(
            competitions: (array) $competitions,
            competition_type: $competition_type,
            player: $player
        );
    }

    /**
     * Present all player competitions grouped by competition type.
     *
     * @param array  $player_competitions Player competitions array.
     * @param object $player              Player object.
     *
     * @return array
     * @throws LogicException Logic exception if competition presenter is missing.
     */
    private function present_competitions_group( array $player_competitions, object $player ): array {
        $presented_competitions = [];

        foreach ( $player_competitions as $competition_type => $competitions ) {
            $presented_competitions[ $competition_type ] = $this->present_competition_entry(
                (string) $competition_type,
                $competitions,
                $player
            );
        }

        return $presented_competitions;
    }

    /**
     * Safely extract a property from an object or array item.
     *
     * @param object|array $item Item object or array.
     * @param string       $key  Property or key name.
     *
     * @return mixed
     */
    private function extract_property( object|array $item, string $key ): mixed {
        if ( is_object( $item ) ) {
            return $item->{$key} ?? null;
        }

        return $item[ $key ] ?? null;
    }

    /**
     * Resolve display name from player object with firstname and surname fallback.
     *
     * @param object      $player    Player object.
     * @param string|null $firstname Firstname.
     * @param string|null $surname   Surname.
     *
     * @return string
     */
    private function resolve_display_name( object $player, ?string $firstname, ?string $surname ): string {
        if ( isset( $player->display_name ) ) {
            return (string) $player->display_name;
        }

        if ( method_exists( $player, 'get_display_name' ) ) {
            return (string) $player->get_display_name();
        }

        return trim( ( $firstname ?? '' ) . ' ' . ( $surname ?? '' ) );
    }

    /**
     * Resolve player statistics array or null.
     *
     * @param object $player Player object.
     *
     * @return array|null
     */
    private function resolve_player_stats( object $player ): ?array {
        if ( isset( $player->stats ) && is_array( $player->stats ) ) {
            return $player->stats;
        }

        if ( isset( $player->statistics ) ) {
            return (array) $player->statistics;
        }

        return null;
    }

    /**
     * Present tournament matches with precalculated URLs.
     *
     * @param string   $tournament Tournament name.
     * @param iterable $matches    Matches collection.
     *
     * @return array
     */
    private function present_tournament_matches( string $tournament, iterable $matches ): array {
        $presented = [];

        foreach ( $matches as $match ) {
            $m                 = is_object( $match ) ? clone $match : (object) $match;
            $t_name            = $m->tournament ?? $tournament;
            $d_name            = $m->draw ?? '';
            $m->tournament_url = '/tournament/' . Util::seo_url( (string) $t_name ) . '/';
            $m->draw_url       = '/tournament/' . Util::seo_url( (string) $t_name ) . '/draw/' . Util::seo_url( (string) $d_name ) . '/';
            $presented[]       = $m;
        }

        return $presented;
    }

    /**
     * Safely extract a string property from an object, or null if unset.
     *
     * @param object $item     Object item.
     * @param string $property Property name.
     *
     * @return string|null
     */
    private function nullable_string( object $item, string $property ): ?string {
        if ( isset( $item->{$property} ) ) {
            return (string) $item->{$property};
        }

        return null;
    }

    /**
     * Resolve year of birth from player object as integer or null.
     *
     * @param object $player Player object.
     *
     * @return int|null
     */
    private function resolve_year_of_birth( object $player ): ?int {
        if ( isset( $player->year_of_birth ) && is_numeric( $player->year_of_birth ) ) {
            return (int) $player->year_of_birth;
        }

        return null;
    }

    /**
     * Convert a value to a string if truthy, or null if falsy.
     *
     * @param mixed $value Raw value.
     *
     * @return string|null
     */
    private function truthy_string( mixed $value ): ?string {
        if ( $value ) {
            return (string) $value;
        }

        return null;
    }

    /**
     * Cast value to nullable integer.
     *
     * @param mixed $value Raw value.
     *
     * @return int|null
     */
    private function nullable_int( mixed $value ): ?int {
        if ( null !== $value ) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Cast value to nullable boolean.
     *
     * @param mixed $value Raw value.
     *
     * @return bool|null
     */
    private function nullable_bool( mixed $value ): ?bool {
        if ( null !== $value ) {
            return (bool) $value;
        }

        return null;
    }
}
