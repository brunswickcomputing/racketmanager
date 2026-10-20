<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Strict immutable view model for player profile, header, and directory views.
 */
final readonly class Player_View_Model {
    /**
     * @param string      $template            Template name or suffix ('player', 'players', 'players-list').
     * @param int         $id                  Player unique ID.
     * @param string|null $firstname           Player first name.
     * @param string|null $surname             Player surname.
     * @param string      $display_name        Player formatted display name.
     * @param string|null $btm                 LTA / BTM tennis membership number.
     * @param string|null $gender              Player gender ('M', 'F').
     * @param int|null    $year_of_birth       Player birth year.
     * @param string|null $email               Player contact email address.
     * @param string|null $contactno           Player telephone / contact number.
     * @param string|null $registration_date   Date player was registered in system.
     * @param string|null $registered_by_user  Name of user who registered the player.
     * @param string      $link                Canonical player profile URL link.
     * @param string      $initials            Player initials for avatar/badge.
     * @param int         $header_level        HTML heading tag level (1-6).
     * @param bool        $is_page_header      Whether this is the main page header.
     * @param array       $wtn_badges          Formatted WTN rating badges.
     * @param array|null  $win_loss_summary    Formatted win/loss summary statistics.
     * @param array       $clubs               Clubs associated with the player.
     * @param array       $titles              Titles / tournament awards won by the player.
     * @param array       $player_competitions Competitions (leagues, cups, tournaments) for player.
     * @param array|null  $stats               Player statistics breakdown.
     * @param bool        $user_can_update     Whether current user has edit permissions.
     * @param string      $search_string       Current player search input value.
     * @param string|null $search_results      Pre-rendered search results HTML.
     * @param array       $favourites          User favourite players list.
     * @param array       $alphabet_nav        Alphabet navigation items for player directory.
     * @param array       $players_list        Grouped player listing by letter.
     * @param string|null $error_message       Error message if player not found.
     * @param object|null $player              Domain entity or object for backward-compatible template access.
     */
    public function __construct(
        public string $template = 'player',
        public int $id = 0,
        public ?string $firstname = null,
        public ?string $surname = null,
        public string $display_name = '',
        public ?string $btm = null,
        public ?string $gender = null,
        public ?int $year_of_birth = null,
        public ?string $email = null,
        public ?string $contactno = null,
        public ?string $registration_date = null,
        public ?string $registered_by_user = null,
        public string $link = '',
        public string $initials = '',
        public int $header_level = 1,
        public bool $is_page_header = false,
        public array $wtn_badges = [],
        public ?array $win_loss_summary = null,
        public array $clubs = [],
        public array $titles = [],
        public array $player_competitions = [],
        public ?array $stats = null,
        public bool $user_can_update = false,
        public string $search_string = '',
        public ?string $search_results = null,
        public array $favourites = [],
        public array $alphabet_nav = [],
        public array $players_list = [],
        public ?string $error_message = null,
        public ?object $player = null
    ) {}

    /**
     * Factory for error view model.
     */
    public static function for_error( string $error_message, string $template = 'alert' ): self {
        return new self(
            template: $template,
            error_message: $error_message
        );
    }

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'vm'                  => $this,
            'player'              => $this->player,
            'clubs'               => $this->clubs,
            'titles'              => $this->titles,
            'player_competitions' => $this->player_competitions,
            'wtn_badges'          => $this->wtn_badges,
            'win_loss_summary'    => $this->win_loss_summary,
            'player_initials'     => $this->initials,
            'header_level'        => $this->header_level,
            'is_page_header'      => $this->is_page_header,
            'user_can_update'     => $this->user_can_update,
            'search_string'       => $this->search_string,
            'search_results'      => $this->search_results,
            'favourites'          => $this->favourites,
            'favourite_name'      => 'player',
            'alphabet_nav'        => $this->alphabet_nav,
            'players_list'        => $this->players_list,
            'player_list'         => $this->players_list,
        ];
    }
}
