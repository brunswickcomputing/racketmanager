<?php
/**
 * Player presenter port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Presentation/Ports
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Presentation\Ports;

use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Player_View_Model;

/**
 * Interface for presenting player view models and templates.
 */
interface Player_Presenter_Interface {

    /**
     * Present full immutable Player view model for player profile and details.
     *
     * @param object     $player              Player object.
     * @param array      $clubs               Clubs array.
     * @param array      $titles              Titles array.
     * @param array      $player_competitions Player competitions array.
     * @param bool       $is_page_header      Page header.
     * @param mixed|null $header_level        Header level.
     * @param bool       $user_can_update     Can user update.
     * @param string     $template            Template.
     *
     * @return Player_View_Model
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
    ): Player_View_Model;

    /**
     * Present player directory view model.
     *
     * @param string      $search_string  Search query.
     * @param string|null $search_results Pre-rendered search results.
     * @param array       $favourites     Favourites array.
     * @param string      $template       Template.
     *
     * @return Player_View_Model
     */
    public function present_players_directory(
        string $search_string = '',
        ?string $search_results = null,
        array $favourites = [],
        string $template = 'players'
    ): Player_View_Model;

    /**
     * Present players list view model.
     *
     * @param array  $players_list Players list array.
     * @param array  $active_keys  Active keys.
     * @param string $template     Template.
     *
     * @return Player_View_Model
     */
    public function present_players_list(
        array $players_list,
        array $active_keys = [],
        string $template = 'players-list'
    ): Player_View_Model;

    /**
     * Get player initials.
     *
     * @param string|null $firstname First name.
     * @param string|null $surname   Surname.
     *
     * @return string
     */
    public function get_initials( ?string $firstname, ?string $surname ): string;

    /**
     * Get heading level.
     *
     * @param bool       $is_page_header Whether it is a page header.
     * @param mixed|null $header_level   Header level.
     *
     * @return int
     */
    public function get_heading_level( bool $is_page_header = false, mixed $header_level = null ): int;

    /**
     * Get WTN badges.
     *
     * @param mixed $wtn WTN data.
     *
     * @return array
     */
    public function get_wtn_badges( mixed $wtn ): array;

    /**
     * Get win/loss summary.
     *
     * @param mixed $statistics Statistics data.
     *
     * @return array|null
     */
    public function get_win_loss_summary( mixed $statistics ): ?array;

    /**
     * Get player link.
     *
     * @param string      $player_name    Player name.
     * @param string|null $club_shortcode Club shortcode.
     * @param string|null $base_url       Base URL.
     *
     * @return string
     */
    public function get_player_link( string $player_name, ?string $club_shortcode = null, ?string $base_url = null ): string;

    /**
     * Get club link.
     *
     * @param string $club_name Club name.
     *
     * @return string
     */
    public function get_club_link( string $club_name ): string;

    /**
     * Get alphabet navigation.
     *
     * @param array $active_keys Active alphabet keys.
     *
     * @return array
     */
    public function get_alphabet_navigation( array $active_keys ): array;

    /**
     * Format date.
     *
     * @param string|null $date Date string.
     *
     * @return string
     */
    public function format_date( ?string $date ): string;

    /**
     * Present club membership view model.
     *
     * @param object|array $club_player Club player data.
     *
     * @return Club_Membership_View_Model
     */
    public function present_club_membership( object|array $club_player ): Club_Membership_View_Model;

    /**
     * Present league player links.
     *
     * @param object $league  League object.
     * @param array  $players Players array.
     *
     * @return array
     */
    public function present_league_player_links( object $league, array $players ): array;
}
