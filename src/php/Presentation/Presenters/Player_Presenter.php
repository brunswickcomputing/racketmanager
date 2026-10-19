<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Player;
use Racketmanager\Domain\Value_Objects\Player_Ranking;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Player_View_Model;
use Racketmanager\Util\Util_Lookup;
use function get_option;
use function mysql2date;
use function Racketmanager\seo_url;
use function str_starts_with;

/**
 * Presenter for Player domain objects and templates.
 */
readonly class Player_Presenter {

    public function __construct(
        private readonly ?Competition_Shortcode_Presenter $competition_presenter = null,
        private readonly ?Option_Gateway_Interface $option_gateway = null
    ) {}

    /**
     * Present full immutable Player view model for player profile and details.
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
        $id           = (int) ( $player->id ?? ( $player->ID ?? ( method_exists( $player, 'get_id' ) ? $player->get_id() : 0 ) ) );
        $firstname    = isset( $player->firstname ) ? (string) $player->firstname : null;
        $surname      = isset( $player->surname ) ? (string) $player->surname : null;
        $display_name = (string) ( $player->display_name ?? ( method_exists( $player, 'get_display_name' ) ? $player->get_display_name() : ( trim( (string) $firstname . ' ' . (string) $surname ) ) ) );
        $btm          = isset( $player->btm ) ? (string) $player->btm : null;
        $gender       = isset( $player->gender ) ? (string) $player->gender : null;
        $yob          = isset( $player->year_of_birth ) && is_numeric( $player->year_of_birth ) ? (int) $player->year_of_birth : null;
        $email        = isset( $player->email ) ? (string) $player->email : null;
        $contactno    = isset( $player->contactno ) ? (string) $player->contactno : null;
        $reg_date     = isset( $player->registration_date ) ? (string) $player->registration_date : null;
        $reg_user     = isset( $player->registered_by_user ) ? (string) $player->registered_by_user : null;
        $link         = isset( $player->link ) ? (string) $player->link : $this->get_player_link( $display_name );
        $stats        = isset( $player->stats ) && is_array( $player->stats ) ? $player->stats : ( isset( $player->statistics ) ? (array) $player->statistics : null );

        $presented_competitions = [];
        foreach ( $player_competitions as $competition_type => $competitions ) {
            if ( ! empty( $competitions ) ) {
                $first = reset( $competitions );
                if ( $first instanceof Competition_List_Item_View_Model ) {
                    $presented_competitions[ $competition_type ] = $competitions;
                } elseif ( $this->competition_presenter ) {
                    $presented_competitions[ $competition_type ] = $this->competition_presenter->present_competition_list(
                        competitions: (array) $competitions,
                        competition_type: (string) $competition_type,
                        player: $player
                    );
                } else {
                    throw new \LogicException( 'A Competition_Shortcode_Presenter is required to present raw player competitions.' );
                }
            } else {
                $presented_competitions[ $competition_type ] = [];
            }
        }

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
            clubs: $clubs,
            titles: $titles,
            player_competitions: $presented_competitions,
            stats: $stats,
            user_can_update: $user_can_update,
            player: $player
        );
    }

    /**
     * Present player search and directory view model.
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
     */
    public function get_initials( ?string $firstname, ?string $surname ): string {
        $first_initial = ! empty( $firstname ) ? mb_substr( trim( (string) $firstname ), 0, 1 ) : '';
        $last_initial  = ! empty( $surname ) ? mb_substr( trim( (string) $surname ), 0, 1 ) : '';

        return mb_strtoupper( $first_initial . $last_initial );
    }

    /**
     * Get heading level for player header.
     */
    public function get_heading_level( bool $is_page_header = false, mixed $header_level = null ): int {
        if ( ! empty( $header_level ) ) {
            return (int) $header_level;
        }

        return $is_page_header ? 1 : 3;
    }

    /**
     * Format WTN ratings into badge presentation structures.
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
                ?? $wtn[ strtolower( (string) $match_type ) ] 
                ?? $wtn[ strtolower( $description ) ] 
                ?? $wtn[ $description ] 
                ?? null;

            if ( null !== $val ) {
                $ranking = Player_Ranking::from_raw( $val, (string) $match_type );

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
     */
    public function get_win_loss_summary( mixed $statistics ): ?array {
        if ( ! is_array( $statistics ) && ! is_object( $statistics ) ) {
            return null;
        }

        $stats_array = (array) $statistics;
        if ( empty( $stats_array['total'] ) ) {
            return null;
        }

        $total   = (object) $stats_array['total'];
        $played  = (int) ( $total->played ?? 0 );
        if ( $played <= 0 ) {
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
     */
    public function get_player_link( string $player_name, ?string $club_shortcode = null, ?string $base_url = null ): string {
        $slug = function_exists( 'Racketmanager\seo_url' ) ? seo_url( $player_name ) : sanitize_title( $player_name );

        if ( ! empty( $base_url ) ) {
            return rtrim( $base_url, '/' ) . '/' . $slug . '/';
        }

        if ( ! empty( $club_shortcode ) ) {
            $club_slug = function_exists( 'Racketmanager\seo_url' ) ? seo_url( $club_shortcode ) : sanitize_title( $club_shortcode );
            return '/clubs/' . $club_slug . '/players/' . $slug . '/';
        }

        return '/players/' . $slug . '/';
    }

    /**
     * Generate alphabet navigation array.
     */
    public function get_alphabet_navigation( array $active_keys ): array {
        $navigation = array();
        foreach ( range( 'A', 'Z' ) as $char ) {
            $is_active = in_array( $char, $active_keys, true );
            $navigation[] = array(
                'letter'      => $char,
                'is_active'   => $is_active,
                'class'       => $is_active ? null : 'is-disabled',
            );
        }

        return $navigation;
    }

    /**
     * Format a date string using the configured date format.
     */
    public function format_date( ?string $date ): string {
        if ( empty( $date ) || str_starts_with( (string) $date, '0000-00-00' ) ) {
            return '';
        }
        $date_format = $this->option_gateway?->get_option( 'date_format', 'd/m/Y' );
        if ( empty( $date_format ) || ! is_string( $date_format ) ) {
            $date_format = function_exists( 'get_option' ) ? get_option( 'date_format', 'd/m/Y' ) : 'd/m/Y';
        }
        if ( empty( $date_format ) || ! is_string( $date_format ) ) {
            $date_format = 'd/m/Y';
        }
        return (string) mysql2date( $date_format, (string) $date );
    }

    /**
     * Present a single club membership item with preformatted display dates.
     */
    public function present_club_membership( object|array $club_player ): Club_Membership_View_Model {
        $club_name         = is_object( $club_player ) ? ( $club_player->club_name ?? '' ) : ( $club_player['club_name'] ?? '' );
        $club_display_name = is_object( $club_player ) ? ( $club_player->club_display_name ?? $club_name ) : ( $club_player['club_display_name'] ?? $club_name );
        $approval_date     = is_object( $club_player ) ? ( $club_player->approval_date ?? null ) : ( $club_player['approval_date'] ?? null );
        $removal_date      = is_object( $club_player ) ? ( $club_player->removal_date ?? null ) : ( $club_player['removal_date'] ?? null );
        $registration_date = is_object( $club_player ) ? ( $club_player->registration_date ?? null ) : ( $club_player['registration_date'] ?? null );
        $club_id           = is_object( $club_player ) ? ( $club_player->club_id ?? null ) : ( $club_player['club_id'] ?? null );
        $user_id           = is_object( $club_player ) ? ( $club_player->user_id ?? null ) : ( $club_player['user_id'] ?? null );
        $registration_id   = is_object( $club_player ) ? ( $club_player->registration_id ?? null ) : ( $club_player['registration_id'] ?? null );

        return new Club_Membership_View_Model(
            club_name: (string) $club_name,
            club_display_name: (string) $club_display_name,
            approval_date: $approval_date ? (string) $approval_date : null,
            approval_date_display: $this->format_date( $approval_date ? (string) $approval_date : null ),
            removal_date: $removal_date ? (string) $removal_date : null,
            removal_date_display: $this->format_date( $removal_date ? (string) $removal_date : null ),
            registration_date: $registration_date ? (string) $registration_date : null,
            club_id: null !== $club_id ? (int) $club_id : null,
            user_id: null !== $user_id ? (int) $user_id : null,
            registration_id: null !== $registration_id ? (int) $registration_id : null,
        );
    }
}
