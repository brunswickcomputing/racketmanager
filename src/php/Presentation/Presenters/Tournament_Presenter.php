<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use DateTimeImmutable;
use Exception;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Enums\Match_Outcome;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use LogicException;
use Racketmanager\Presentation\View_Models\Tournament_Date_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Draw_List_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Event_List_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Header_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Player_Entry_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_Opponent_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_Set_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_Title_Item_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Date_Tab_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Group_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Matches_View_Model;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Util\Util;
use function __;
use function get_current_user_id;
use function is_array;
use function is_object;
use function is_user_logged_in;
use function mysql2date;
use function Racketmanager\seo_url;
use function trim;

/**
 * Presenter formatting Tournament dates and links for display (keeps formatting out of the domain entity).
 */
readonly class Tournament_Presenter {

    public function __construct(
        private string $date_format = 'Y-m-d',
        private ?Fixture_Detail_Service $fixture_detail_service = null,
        private ?Team_Match_Row_Presenter $team_match_row_presenter = null
    ) {}

    /**
     * Build the display dates view model for a tournament.
     */
    public function present_dates( object $tournament ): Tournament_Date_View_Model {
        $tournament_window = ( $tournament instanceof Tournament )
            ? $tournament->get_tournament_window()
            : ( method_exists( $tournament, 'get_tournament_window' ) ? $tournament->get_tournament_window() : null );
        $entry_window      = ( $tournament instanceof Tournament )
            ? $tournament->get_entry_window()
            : ( method_exists( $tournament, 'get_entry_window' ) ? $tournament->get_entry_window() : null );
        $withdrawal_window = ( $tournament instanceof Tournament )
            ? $tournament->get_withdrawal_window()
            : ( method_exists( $tournament, 'get_withdrawal_window' ) ? $tournament->get_withdrawal_window() : null );

        $end_date        = method_exists( $tournament, 'get_end_date' ) ? $tournament->get_end_date() : ( $tournament->date_end ?? ( $tournament->date ?? null ) );
        $closing_date    = method_exists( $tournament, 'get_closing_date' ) ? $tournament->get_closing_date() : ( $tournament->date_closing ?? null );
        $withdrawal_date = method_exists( $tournament, 'get_withdrawal_date' ) ? $tournament->get_withdrawal_date() : ( $tournament->date_withdrawal ?? null );
        $open_date       = method_exists( $tournament, 'get_open_date' ) ? $tournament->get_open_date() : ( $tournament->date_open ?? null );
        $start_date      = method_exists( $tournament, 'get_start_date' ) ? $tournament->get_start_date() : ( $tournament->date_start ?? null );

        $end_display = $tournament_window ? $this->format( $tournament_window->get_end_string(), 'TBC' ) : $this->format( $end_date, 'TBC' );

        return new Tournament_Date_View_Model(
            date_display: $end_display,
            date_closing_display: $entry_window ? $this->format( $entry_window->get_end_string(), 'N/A' ) : $this->format( $closing_date, 'N/A' ),
            date_withdrawal_display: $withdrawal_window ? $this->format( $withdrawal_window->get_end_string(), 'N/A' ) : $this->format( $withdrawal_date, 'N/A' ),
            date_open_display: $entry_window ? $this->format( $entry_window->get_start_string(), 'N/A' ) : $this->format( $open_date, 'N/A' ),
            date_start_display: $tournament_window ? $this->format( $tournament_window->get_start_string(), 'N/A' ) : $this->format( $start_date, 'N/A' ),
            date_end_display: $end_display
        );
    }

    /**
     * Build the display header view model for a tournament.
     */
    public function present_header( object $tournament, bool $entry_option = false ): Tournament_Header_View_Model {
        $name = is_object( $tournament ) && isset( $tournament->name ) ? (string) $tournament->name : ( method_exists( $tournament, 'get_name' ) ? (string) $tournament->get_name() : '' );
        $venue_name = method_exists( $tournament, 'get_meta' ) ? ( $tournament->get_meta( 'venue_name' ) ?: null ) : ( $tournament->venue_name ?? null );

        $tournament_window = ( $tournament instanceof Tournament )
            ? $tournament->get_tournament_window()
            : ( method_exists( $tournament, 'get_tournament_window' ) ? $tournament->get_tournament_window() : null );

        if ( $tournament_window ) {
            $has_date_range     = true;
            $date_start_display = (string) mysql2date( $this->date_format, $tournament_window->get_start_string() );
            $date_end_display   = (string) mysql2date( $this->date_format, $tournament_window->get_end_string() );
        } else {
            $date_start = $tournament->date_start ?? ( method_exists( $tournament, 'get_start_date' ) ? $tournament->get_start_date() : null );
            $date_end   = $tournament->date ?? $tournament->date_end ?? ( method_exists( $tournament, 'get_end_date' ) ? $tournament->get_end_date() : null );
            $range      = Date_Range::try_create( $date_start, $date_end );
            if ( $range ) {
                $has_date_range     = true;
                $date_start_display = (string) mysql2date( $this->date_format, $range->get_start_string() );
                $date_end_display   = (string) mysql2date( $this->date_format, $range->get_end_string() );
            } else {
                $has_date_range     = ! empty( $date_start ) && ! empty( $date_end );
                $date_start_display = $has_date_range ? (string) mysql2date( $this->date_format, (string) $date_start ) : null;
                $date_end_display   = $has_date_range ? (string) mysql2date( $this->date_format, (string) $date_end ) : null;
            }
        }

        $is_open = (bool) ( $tournament->is_open ?? ( method_exists( $tournament, 'is_open' ) ? $tournament->is_open() : false ) );
        $show_entry_button = $is_open && $entry_option;
        $entry_url = $show_entry_button ? '/tournament/entry-form/' . Util::seo_url( $name ) . '/' : null;

        return new Tournament_Header_View_Model(
            name: $name,
            venue_name: $venue_name,
            date_start_display: $date_start_display,
            date_end_display: $date_end_display,
            has_date_range: $has_date_range,
            show_entry_button: $show_entry_button,
            entry_url: $entry_url,
        );
    }

    /**
     * Build the tournament permalink relative URL.
     */
    public function get_link( object $tournament ): string {
        $name = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );

        return '/tournament/' . Util::seo_url( (string) $name ) . '/';
    }

    /**
     * Build the tournament event permalink relative URL.
     */
    public function get_event_link( object $tournament, object|string $event ): string {
        $event_name = is_object( $event ) ? ( $event->name ?? '' ) : (string) $event;

        return $this->get_link( $tournament ) . 'event/' . Util::seo_url( (string) $event_name ) . '/';
    }

    /**
     * Build the tournament draw permalink relative URL.
     */
    public function get_draw_link( object $tournament, object|string $event ): string {
        $event_name = is_object( $event ) ? ( $event->name ?? '' ) : (string) $event;

        return $this->get_link( $tournament ) . 'draw/' . Util::seo_url( (string) $event_name ) . '/';
    }

    /**
     * Build the tournament player permalink relative URL.
     */
    public function get_player_link( object $tournament, object|string $player ): string {
        $player_name = is_object( $player )
            ? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->fullname ?? $player->name ?? $player->display_name ?? '' ) )
            : (string) $player;

        return $this->get_link( $tournament ) . 'player/' . Util::seo_url( (string) $player_name ) . '/';
    }

    /**
     * Build the tournament payment-complete URL.
     */
    public function get_payment_complete_url( object $tournament, string $site_url = '' ): string {
        return rtrim( $this->get_entry_link( $tournament, $site_url ), '/' ) . '/payment-complete/';
    }

    /**
     * @param array<int, object> $events
     * @return list<Tournament_Event_List_Row_View_Model>
     */
    public function present_event_list_rows( object $tournament, array $events ): array {
        $rows = [];
        foreach ( $events as $event ) {
            $rows[] = new Tournament_Event_List_Row_View_Model(
                id: $event->id ?? 0,
                name: (string) ( $event->name ?? '' ),
                num_teams: $event->num_teams ?? null,
                url_link: $this->get_event_link( $tournament, $event ),
            );
        }

        return $rows;
    }

    /**
     * @param array<int, object> $draws
     * @return list<Tournament_Draw_List_Row_View_Model>
     */
    public function present_draw_list_rows( object $tournament, array $draws ): array {
        $rows = [];
        foreach ( $draws as $event ) {
            $rows[] = new Tournament_Draw_List_Row_View_Model(
                id: $event->id ?? 0,
                name: (string) ( $event->name ?? '' ),
                draw_size: $event->draw_size ?? null,
                draw_link: $this->get_draw_link( $tournament, $event ),
            );
        }

        return $rows;
    }

    /**
     * Build per-player permalinks for a tournament event's entries.
     *
     * @return array<int, string>
     */
    public function present_event_player_links( object $tournament, object $event ): array {
        $links   = [];
        $entries = is_array( $event->entries ?? null ) ? $event->entries : [];
        foreach ( $entries as $entry ) {
            $players = is_array( $entry->players ?? null ) ? $entry->players : [];
            foreach ( $players as $player ) {
                $player_id = is_object( $player ) ? ( $player->id ?? $player->ID ?? null ) : null;
                if ( null === $player_id ) {
                    continue;
                }
                $links[ (int) $player_id ] = $this->get_player_link( $tournament, $player );
            }
        }

        return $links;
    }

    /**
     * Build per-player permalinks for an alphabet-grouped tournament player list.
     *
     * @param array<string, array<int, object>> $grouped_players
     * @return array<int, string>
     */
    public function present_player_list_links( object $tournament, array $grouped_players ): array {
        $links = [];
        foreach ( $grouped_players as $players ) {
            if ( ! is_array( $players ) ) {
                continue;
            }
            foreach ( $players as $player ) {
                $player_id = is_object( $player ) ? ( $player->id ?? $player->ID ?? null ) : null;
                if ( null === $player_id ) {
                    continue;
                }
                $links[ (int) $player_id ] = $this->get_player_link( $tournament, $player );
            }
        }

        return $links;
    }

    /**
     * @param array<int, object> $entries
     * @return list<Tournament_Player_Entry_Row_View_Model>
     */
    public function present_player_entry_rows( object $tournament, array $entries ): array {
        $rows = [];
        foreach ( $entries as $entry ) {
            $partner_name = (string) ( $entry->partner_name ?? '' );
            $rows[]       = new Tournament_Player_Entry_Row_View_Model(
                event_id: $entry->event_id ?? 0,
                event_name: (string) ( $entry->event_name ?? '' ),
                event_link: $this->get_event_link( $tournament, (string) ( $entry->event_name ?? '' ) ),
                partner_id: $entry->partner_id ?? null,
                partner_name: $partner_name,
                partner_link: '' !== $partner_name ? $this->get_player_link( $tournament, $partner_name ) : '',
            );
        }

        return $rows;
    }

    /**
     * Build template variables for a single tournament match view.
     *
     * @param object|null $tournament
     * @param Fixture_Details_DTO|null $fixture_details
     * @param object|null $is_update_allowed
     * @param string|null $referrer Referring page supplied by the request boundary, used for the return link.
     * @return array<string, mixed>
     */
    public function present_tournament_match_data( ?object $tournament, ?Fixture_Details_DTO $fixture_details, ?object $is_update_allowed, ?string $referrer = null ): array {
        $tournament_link     = $tournament ? $this->get_link( $tournament ) : '';
        $back_url            = ( null !== $referrer && '' !== $referrer )
            ? $referrer
            : ( $tournament ? $tournament_link . 'matches/' : '' );
        $tournament_header   = $tournament ? $this->present_header( $tournament ) : null;
        $tournament_seo_name = $tournament ? Util::seo_url( $tournament->name ?? '' ) : '';

        $tournament_date_range_display = '';
        $date_start = null;
        $date_end   = null;
        if ( $tournament ) {
            $date_start = $tournament->date_start ?? ( method_exists( $tournament, 'get_start_date' ) ? $tournament->get_start_date() : null );
            $date_end   = $tournament->date ?? $tournament->date_end ?? ( method_exists( $tournament, 'get_end_date' ) ? $tournament->get_end_date() : null );
        }
        if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
            $start_fmt = $this->format( $date_start, '' );
            $end_fmt   = $this->format( $date_end, '' );
            if ( '' !== $start_fmt && '' !== $end_fmt ) {
                $tournament_date_range_display = $start_fmt . ' ' . __( 'to', 'racketmanager' ) . ' ' . $end_fmt;
            }
        }

        $match_obj = $fixture_details?->fixture;
        $league    = $fixture_details?->league;
        $event     = $fixture_details?->event;

        $match_date = ! empty( $match_obj?->date ) ? (string) $match_obj->date : '';
        $match_date_formatted = '';
        $match_time_formatted = '';
        if ( '' !== $match_date && ! str_starts_with( $match_date, '0000-00-00' ) ) {
            $formatted_date = mysql2date( 'D j M', $match_date );
            $match_date_formatted = false !== $formatted_date ? (string) $formatted_date : '';
            $start_time = (string) ( $match_obj->start_time ?? '' );
            if ( '' !== $start_time && '00:00' !== $start_time && '00:00:00' !== $start_time ) {
                $formatted_time = mysql2date( 'G:i', $match_date );
                $match_time_formatted = false !== $formatted_time ? (string) $formatted_time : '';
            }
        }

        $svg_link_text     = '';
        $svg_link          = '';
        $svg_link_location = '';
        if ( $league && $event ) {
            if ( ! empty( $league->is_championship ) ) {
                $svg_link_text     = __( 'Draw', 'racketmanager' );
                $svg_link          = $event->name;
                $svg_link_location = $tournament_link . 'draw/' . Util::seo_url( $event->name ) . '/';
            } else {
                $season_text       = ! empty( $event->is_box ) ? __( 'round', 'racketmanager' ) . '-' . ( $match_obj?->season ?? '' ) : ( $match_obj?->season ?? '' );
                $svg_link_text     = __( 'League', 'racketmanager' );
                $svg_link          = $league->title;
                $svg_link_location = '/league/' . Util::seo_url( $league->title ) . '/' . $season_text . '/';
            }
        }

        $round_name = ! empty( $match_obj?->final ) ? Util::get_final_name( $match_obj->final ) : '';

        $player_urls = [];
        if ( $fixture_details ) {
            foreach ( [ $fixture_details->home_team, $fixture_details->away_team ] as $team_details ) {
                $team = $team_details?->team;
                if ( ! empty( $team?->players ) && is_array( $team->players ) ) {
                    foreach ( $team->players as $player ) {
                        if ( is_object( $player ) && '' !== $tournament_seo_name ) {
                            $player_id = method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? $player->ID ?? null );
                            $fullname  = method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->fullname ?? '' );
                            $url       = '/tournament/' . $tournament_seo_name . '/player/' . Util::seo_url( trim( $fullname ) );
                            if ( $player_id ) {
                                $player_urls[ $player_id ] = $url;
                            }
                            if ( '' !== $fullname ) {
                                $player_urls[ $fullname ] = $url;
                            }
                        }
                    }
                }
            }
        }

        $user_can_update          = is_object( $is_update_allowed ) && ! empty( $is_update_allowed->user_can_update );
        $user_type                = is_object( $is_update_allowed ) ? (string) ( $is_update_allowed->user_type ?? '' ) : '';
        $match_editable           = $user_can_update ? 'is-editable' : false;
        $allow_schedule_match     = false;
        $allow_reset_match_result = false;
        $show_menu                = false;
        $winner                   = null;
        $loser                    = null;
        $is_tie                   = false;
        $match_status             = null;
        $player_team              = null;
        $player_team_status       = null;
        $tournament_head          = ( $tournament && ! empty( $tournament->name ) ) ? $tournament->name . ' ' : '';
        $tournament_head         .= __( 'Tournament', 'racketmanager' );

        $competition      = $fixture_details?->competition;
        $competition_type = ( $competition && method_exists( $competition, 'get_type' ) ) ? (string) $competition->get_type() : '';
        $image            = match ( $competition_type ) {
            'league'     => 'assets/icons/bootstrap-icons.svg#table',
            'cup'        => 'assets/icons/bootstrap-icons.svg#trophy-fill',
            'tournament' => 'assets/icons/lta-icons.svg#icon-bracket',
            default      => null,
        };

        $home_team = $fixture_details?->home_team;
        $away_team = $fixture_details?->away_team;
        if ( $match_obj ) {
            $winner_id = $match_obj->winner_id ?? null;
            if ( ! empty( $winner_id ) ) {
                if ( 'admin' === $user_type ) {
                    $allow_reset_match_result = true;
                    $show_menu                = true;
                }
                $home_id = ( $home_team && $home_team->team && method_exists( $home_team->team, 'get_id' ) ) ? $home_team->team->get_id() : null;
                $away_id = ( $away_team && $away_team->team && method_exists( $away_team->team, 'get_id' ) ) ? $away_team->team->get_id() : null;
                if ( $winner_id === $home_id ) {
                    $winner = 'home';
                    $loser  = 'away';
                } elseif ( $winner_id === $away_id ) {
                    $winner = 'away';
                    $loser  = 'home';
                } elseif ( '-1' === $winner_id ) {
                    $is_tie = true;
                }
                if ( $winner === $player_team ) {
                    $player_team_status = 'winner';
                } elseif ( $loser === $player_team ) {
                    $player_team_status = 'loser';
                }
                $custom = is_array( $match_obj->custom ?? null ) ? $match_obj->custom : [];
                if ( ! empty( $match_obj->is_walkover ) && ! empty( $custom['walkover'] ) ) {
                    if ( 'home' === $custom['walkover'] ) {
                        $match_status = 'walkover_player1';
                    } elseif ( 'away' === $custom['walkover'] ) {
                        $match_status = 'walkover_player2';
                    }
                } elseif ( ! empty( $match_obj->is_retired ) ) {
                    if ( 'home' === ( $custom['retired'] ?? null ) ) {
                        $match_status = 'retired_player1';
                    } elseif ( 'away' === ( $custom['retired'] ?? null ) ) {
                        $match_status = 'retired_player2';
                    }
                } elseif ( ! empty( $match_obj->is_shared ) ) {
                    $match_status = 'share';
                }
            } elseif ( $user_can_update ) {
                $allow_schedule_match = true;
                $show_menu            = true;
            }
        }

        return [
            'tournament'                    => $tournament,
            'tournament_header'             => $tournament_header,
            'tournament_link'               => $tournament_link,
            'back_url'                      => $back_url,
            'tournament_seo_name'           => $tournament_seo_name,
            'tournament_date_range_display' => $tournament_date_range_display,
            'fixture_details'               => $fixture_details,
            'is_update_allowed'             => $is_update_allowed,
            'match_date_formatted'          => $match_date_formatted,
            'match_time_formatted'          => $match_time_formatted,
            'svg_link_text'                 => $svg_link_text,
            'svg_link'                      => $svg_link,
            'svg_link_location'             => $svg_link_location,
            'round_name'                    => $round_name,
            'player_urls'                   => $player_urls,
            'user_can_update'               => $user_can_update,
            'match_editable'                => $match_editable,
            'allow_schedule_match'          => $allow_schedule_match,
            'allow_reset_match_result'      => $allow_reset_match_result,
            'show_menu'                     => $show_menu,
            'winner'                        => $winner,
            'loser'                         => $loser,
            'is_tie'                        => $is_tie,
            'match_status'                  => $match_status,
            'player_team'                   => $player_team,
            'player_team_status'            => $player_team_status,
            'tournament_head'               => $tournament_head,
            'image'                         => $image,
        ];
    }

    /**
     * Build the tournament entry form URL.
     */
    public function get_entry_link( object $tournament, string $site_url = '' ): string {
        $name = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );

        return rtrim( $site_url, '/' ) . '/entry-form/' . seo_url( (string) $name ) . '-tournament/';
    }

    /**
     * Format a list of tournament fixture dates into date tab view models.
     *
     * @param object $tournament
     * @param array<string> $dates
     * @param string|null $current_date
     * @return array<Tournament_Match_Date_Tab_View_Model>
     */
    public function present_match_dates( object $tournament, array $dates, ?string $current_date = null ): array {
        $name = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );
        $tabs = [];

        foreach ( $dates as $date ) {
            $is_selected = ( $current_date === (string) $date );
            $tabs[] = new Tournament_Match_Date_Tab_View_Model(
                date: (string) $date,
                matches_link: '/tournament/' . seo_url( (string) $name ) . '/matches/' . $date . '/',
                weekday: (string) mysql2date( 'D', (string) $date ),
                day: (string) mysql2date( 'j', (string) $date ),
                month: (string) mysql2date( 'M', (string) $date ),
                formatted_select: (string) mysql2date( 'D j M', (string) $date ),
                is_selected: $is_selected,
                selected_class: $is_selected ? 'is_selected' : ''
            );
        }

        return $tabs;
    }

    /**
     * Build the matches view model for a tournament.
     *
     * @param object $tournament
     * @param array<string> $fixture_dates
     * @param string|null $current_date
     * @param array $tournament_fixtures
     * @return Tournament_Matches_View_Model
     */
    public function present_matches(
        object $tournament,
        array $fixture_dates,
        ?string $current_date,
        array $tournament_fixtures
    ): Tournament_Matches_View_Model {
        $id = $tournament->id ?? ( method_exists( $tournament, 'get_id' ) ? $tournament->get_id() : '' );
        $name = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );

        $match_date_tabs = $this->present_match_dates( $tournament, $fixture_dates, $current_date );

        $groups = [];
        foreach ( $tournament_fixtures as $key => $fixtures ) {
            $header = ( '99:99' === (string) $key ) ? __( 'Unscheduled', 'racketmanager' ) : (string) $key;
            $fixture_list = is_array( $fixtures ) ? $fixtures : [];
            $card_vms = array_map(
                fn( $fixture ) => ( $fixture instanceof Tournament_Match_Card_View_Model ) ? $fixture : $this->present_match_card( $fixture, $tournament, null, 'list' ),
                $fixture_list
            );
            $groups[] = new Tournament_Match_Group_View_Model(
                key: (string) $key,
                header: $header,
                fixtures: $card_vms
            );
        }

        return new Tournament_Matches_View_Model(
            tournament_id: $id,
            tournament_name: (string) $name,
            current_match_date: $current_date,
            match_dates: $match_date_tabs,
            match_groups: $groups,
            has_many_dates: count( $match_date_tabs ) > 8,
            has_matches: ! empty( $groups )
        );
    }

    /**
     * Build a list of match card view models.
     *
     * @param array $fixture_details
     * @param object|null $tournament
     * @param object|null $tournament_player
     * @param string|bool $match_display
     * @param bool $location_in_header
     * @return array<Tournament_Match_Card_View_Model>
     */
    public function present_match_cards(
        array $fixture_details,
        ?object $tournament = null,
        ?object $tournament_player = null,
        string|bool $match_display = '',
        bool $location_in_header = false,
        bool $is_tournament_competition = false
    ): array {
        return array_map(
            fn( $f ) => ( $f instanceof Tournament_Match_Card_View_Model ) ? $f : $this->present_match_card( $f, $tournament, $tournament_player, $match_display, $location_in_header, $is_tournament_competition ),
            $fixture_details
        );
    }

    /**
     * Prepare a mixed team-match list for templates/includes/matches-team-list.php.
     *
     * Fixtures belonging to a tournament competition are converted to list-style match cards
     * (rendered by templates/tournament/match.php); all other matches are converted to
     * Team_Match_Row_View_Model via the injected presenter.
     *
     * @param iterable $matches Fixture entities, Fixture_Presentation_Adapter instances, DTOs, or cards.
     * @param bool|null $is_tournament_competition
     * @param array $options
     * @return array
     */
    public function present_match_list( iterable $matches, ?bool $is_tournament_competition = null, array $options = [] ): array {
        $list = [];
        foreach ( $matches as $key => $match ) {
            if ( $match instanceof Tournament_Match_Card_View_Model || $match instanceof Team_Match_Row_View_Model ) {
                $list[ $key ] = $match;
                continue;
            }
            $is_tournament = $is_tournament_competition ?? $this->is_tournament_competition_match( $match );
            if ( $is_tournament ) {
                $fixture      = ( $match instanceof Fixture_Presentation_Adapter ) ? $match->fixture : $match;
                $list[ $key ] = $this->present_match_card( $fixture, null, null, 'list', false, true );
                continue;
            }
            $list[ $key ] = $this->require_row_presenter()->present( $match, $options );
        }

        return $list;
    }

    /**
     * Present a keyed group of matches (date, match day or league) as an immutable group view model.
     *
     * @param string|int $key Group key (date, match day, league name...).
     * @param iterable $matches Matches belonging to the group.
     * @param bool|null $is_tournament_competition
     * @param array $options
     */
    public function present_match_group( string|int $key, iterable $matches, ?bool $is_tournament_competition = null, array $options = [] ): Team_Matches_Group_View_Model {
        $presented = $this->present_match_list( $matches, $is_tournament_competition, $options );

        return $this->require_row_presenter()->present_group( $key, $presented, null, $options );
    }

    private function require_row_presenter(): Team_Match_Row_Presenter {
        if ( null === $this->team_match_row_presenter ) {
            throw new LogicException( 'Tournament_Presenter requires a Team_Match_Row_Presenter to present team match lists.' );
        }

        return $this->team_match_row_presenter;
    }

    /**
     * Whether a list item belongs to a tournament competition.
     */
    public function is_tournament_competition_match( mixed $match ): bool {
        if ( ! is_object( $match ) ) {
            return false;
        }
        $league = ( $match instanceof Fixture ) ? $match->get_league() : ( $match->league ?? null );

        return $this->is_tournament_competition( $league?->event?->competition ?? null );
    }

    /**
     * Whether a competition (entity or legacy read object) is a tournament competition.
     */
    public function is_tournament_competition( ?object $competition ): bool {
        $type  = $competition->type ?? null;
        $value = $type instanceof \BackedEnum ? $type->value : (string) ( $type ?? '' );

        return Competition_Type::TOURNAMENT->value === $value;
    }

    /**
     * Build a match card view model for templates/tournament/match.php.
     *
     * @param mixed $fixture_detail
     * @param object|null $tournament
     * @param object|null $tournament_player
     * @param string|bool $match_display
     * @param bool $location_in_header
     * @param bool $is_tournament_competition Enrich using tournament placeholder rules even without a Tournament.
     * @return Tournament_Match_Card_View_Model
     */
    public function present_match_card(
        mixed $fixture_detail,
        ?object $tournament = null,
        ?object $tournament_player = null,
        string|bool $match_display = '',
        bool $location_in_header = false,
        bool $is_tournament_competition = false
    ): Tournament_Match_Card_View_Model {
        if ( $fixture_detail instanceof Tournament_Match_Card_View_Model ) {
            return $fixture_detail;
        }

        if ( ! is_object( $fixture_detail ) && ! is_int( $fixture_detail ) ) {
            return new Tournament_Match_Card_View_Model( is_missing: true );
        }

        if ( ( $fixture_detail instanceof Fixture || is_int( $fixture_detail ) ) && $this->fixture_detail_service !== null ) {
            try {
                $is_tournament = $tournament !== null || $is_tournament_competition || $this->is_tournament_competition_match( $fixture_detail );
                $enriched = $this->fixture_detail_service->get_fixture_with_details( $fixture_detail, $is_tournament );
                if ( $enriched !== null ) {
                    $fixture_detail = $enriched;
                }
            } catch ( \Racketmanager\Exceptions\Fixture_Not_Found_Exception|\Racketmanager\Exceptions\League_Not_Found_Exception|\Racketmanager\Exceptions\Event_Not_Found_Exception|\Racketmanager\Exceptions\Competition_Not_Found_Exception|\Racketmanager\Exceptions\Team_Not_Found_Exception|\Racketmanager\Exceptions\Club_Not_Found_Exception|\Racketmanager\Exceptions\Invalid_Argument_Exception ) {
                return new Tournament_Match_Card_View_Model(
                    id: is_int( $fixture_detail ) ? $fixture_detail : ( method_exists( $fixture_detail, 'get_id' ) ? $fixture_detail->get_id() : ( $fixture_detail->id ?? null ) ),
                    is_missing: true
                );
            }
        }

        $fixture           = null;
        $league            = null;
        $event             = null;
        $competition       = null;
        $home_team_dto     = null;
        $away_team_dto     = null;
        $is_update_allowed = null;
        $prev_home_title   = null;
        $prev_away_title   = null;

        if ( $fixture_detail instanceof Fixture_Details_DTO ) {
            $fixture           = $fixture_detail->fixture;
            $league            = $fixture_detail->league;
            $event             = $fixture_detail->event;
            $competition       = $fixture_detail->competition;
            $home_team_dto     = $fixture_detail->home_team;
            $away_team_dto     = $fixture_detail->away_team;
            $is_update_allowed = $fixture_detail->is_update_allowed;
            $prev_home_title   = $fixture_detail->prev_home_fixture_title;
            $prev_away_title   = $fixture_detail->prev_away_fixture_title;
        } elseif ( $fixture_detail instanceof Fixture ) {
            $fixture           = $fixture_detail;
            $league            = method_exists( $fixture, 'get_league' ) ? $fixture->get_league() : ( $fixture->league ?? null );
            $event             = $league?->event ?? null;
            $competition       = $event?->competition ?? null;
            $is_update_allowed = $fixture->is_update_allowed ?? null;
            $home_team_dto     = $fixture->home_team_dto ?? null;
            $away_team_dto     = $fixture->away_team_dto ?? null;
            $prev_home_title   = method_exists( $fixture, 'get_prev_home_fixture_title' ) ? $fixture->get_prev_home_fixture_title() : ( $fixture->prev_home_fixture_title ?? null );
            $prev_away_title   = method_exists( $fixture, 'get_prev_away_fixture_title' ) ? $fixture->get_prev_away_fixture_title() : ( $fixture->prev_away_fixture_title ?? null );
            if ( $home_team_dto === null && $away_team_dto === null && empty( $prev_home_title ) && empty( $prev_away_title ) && empty( $fixture->final ) ) {
                return new Tournament_Match_Card_View_Model(
                    id: method_exists( $fixture, 'get_id' ) ? $fixture->get_id() : ( $fixture->id ?? null ),
                    is_missing: true
                );
            }
        } elseif ( is_object( $fixture_detail ) ) {
            $fixture           = $fixture_detail->fixture ?? $fixture_detail;
            $league            = $fixture_detail->league ?? ( $fixture->league ?? null );
            $event             = $fixture_detail->event ?? ( $league?->event ?? null );
            $competition       = $fixture_detail->competition ?? ( $event?->competition ?? null );
            $home_team_dto     = $fixture_detail->home_team ?? ( $fixture->home_team_dto ?? null );
            $away_team_dto     = $fixture_detail->away_team ?? ( $fixture->away_team_dto ?? null );
            $is_update_allowed = $fixture_detail->is_update_allowed ?? ( $fixture->is_update_allowed ?? null );
        }

        $match_display_class = ! empty( $match_display ) ? 'match--list' : '';

        $home_team = $home_team_dto?->team ?? ( is_object( $home_team_dto ) && ( method_exists( $home_team_dto, 'get_name' ) || isset( $home_team_dto->name ) ) ? $home_team_dto : null );
        $away_team = $away_team_dto?->team ?? ( is_object( $away_team_dto ) && ( method_exists( $away_team_dto, 'get_name' ) || isset( $away_team_dto->name ) ) ? $away_team_dto : null );

        $user_can_update = false;
        $match_link      = null;

        if ( $home_team && $away_team ) {
            $home_name    = method_exists( $home_team, 'get_name' ) ? $home_team->get_name() : ( $home_team->name ?? '' );
            $away_name    = method_exists( $away_team, 'get_name' ) ? $away_team->get_name() : ( $away_team->name ?? '' );
            $league_title = $league->title ?? ( is_object( $league ) && method_exists( $league, 'get_name' ) ? $league->get_name() : ( $league->name ?? '' ) );
            $fixture_id   = $fixture ? ( method_exists( $fixture, 'get_id' ) ? $fixture->get_id() : ( $fixture->id ?? 0 ) ) : 0;
            $season       = $fixture ? ( method_exists( $fixture, 'get_season' ) ? $fixture->get_season() : ( $fixture->season ?? '' ) ) : '';

            if ( empty( $tournament ) ) {
                if ( ! empty( $fixture_detail->link ) ) {
                    $match_link = $fixture_detail->link;
                } else {
                    $is_championship = $league ? ( ! empty( $league->is_championship ) || ( method_exists( $league, 'is_championship' ) && $league->is_championship() ) ) : false;
                    if ( $is_championship ) {
                        $final_round = $fixture ? ( method_exists( $fixture, 'get_final' ) ? $fixture->get_final() : ( $fixture->final ?? '' ) ) : '';
                        $match_ref   = ! empty( $final_round ) ? (string) $final_round : (string) ( $league->final_round ?? '' );
                    } else {
                        $match_day = $fixture ? ( method_exists( $fixture, 'get_match_day' ) ? $fixture->get_match_day() : ( $fixture->match_day ?? '' ) ) : ( $league->match_day ?? '' );
                        $match_ref = 'day' . $match_day;
                    }
                    $match_link = '/match/' . seo_url( (string) $league_title ) . '/' . $season . '/' . $match_ref . '/' . seo_url( (string) $home_name ) . '-vs-' . seo_url( (string) $away_name ) . '/';
                }
            } else {
                $tourn_name = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );
                $match_link = '/tournament/' . seo_url( (string) $tourn_name ) . '/match/' . seo_url( (string) $league_title ) . '/' . seo_url( (string) $home_name ) . '-vs-' . seo_url( (string) $away_name ) . '/' . $fixture_id . '/';
            }
            $user_can_update = (bool) ( $is_update_allowed?->user_can_update ?? false );
        }

        $player_team        = null;
        $player_team_status = null;
        if ( ! empty( $tournament_player ) ) {
            $tp_id = (int) ( $tournament_player->id ?? ( method_exists( $tournament_player, 'get_id' ) ? $tournament_player->get_id() : 0 ) );
            if ( $home_team && method_exists( $home_team, 'has_player' ) && $home_team->has_player( $tp_id ) ) {
                $player_team = 'home';
            } elseif ( $away_team && method_exists( $away_team, 'has_player' ) && $away_team->has_player( $tp_id ) ) {
                $player_team = 'away';
            }
        }

        $match_selected = false;
        if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
            $current_user_id = (int) get_current_user_id();
            if ( ( $home_team && method_exists( $home_team, 'has_player' ) && $home_team->has_player( $current_user_id ) ) ||
                 ( $away_team && method_exists( $away_team, 'has_player' ) && $away_team->has_player( $current_user_id ) ) ) {
                $match_selected = true;
            }
        }

        $winner         = null;
        $loser          = null;
        $is_tie         = false;
        $match_complete = false;
        $winner_id      = $fixture ? ( method_exists( $fixture, 'get_winner_id' ) ? $fixture->get_winner_id() : ( $fixture->winner_id ?? null ) ) : null;

        if ( ! empty( $winner_id ) ) {
            $match_complete = true;
            $home_id = $home_team ? ( method_exists( $home_team, 'get_id' ) ? $home_team->get_id() : ( $home_team->id ?? null ) ) : null;
            $away_id = $away_team ? ( method_exists( $away_team, 'get_id' ) ? $away_team->get_id() : ( $away_team->id ?? null ) ) : null;

            if ( $home_team && (string) $winner_id === (string) $home_id ) {
                $winner = 'home';
                $loser  = 'away';
            } elseif ( $away_team && (string) $winner_id === (string) $away_id ) {
                $winner = 'away';
                $loser  = 'home';
            } elseif ( -1 === (int) $winner_id ) {
                $is_tie = true;
            }

            if ( $winner && $winner === $player_team ) {
                $player_team_status = 'winner';
            } elseif ( $loser && $loser === $player_team ) {
                $player_team_status = 'loser';
            }
        }

        $final      = $fixture ? ( method_exists( $fixture, 'get_final' ) ? $fixture->get_final() : ( $fixture->final ?? '' ) ) : '';
        $final_name = (string) ( Util::get_final_name( (string) $final ) ?: '' );

        $tournament_link = '';
        $draw_link       = null;
        $show_draw_link  = false;
        if ( ! empty( $tournament ) ) {
            $tournament_link = $this->get_link( $tournament );
            $event_name      = $league?->event?->name ?? ( $event->name ?? '' );
            $draw_link       = $tournament_link . 'draw/' . seo_url( (string) $event_name ) . '/';
            $show_draw_link  = true;
        }

        $league_title = $league->title ?? ( is_object( $league ) && method_exists( $league, 'get_name' ) ? $league->get_name() : ( $league->name ?? '' ) );

        $fixture_date          = $fixture ? ( method_exists( $fixture, 'get_date' ) ? $fixture->get_date() : ( $fixture->date ?? null ) ) : null;
        $start_time            = $fixture ? ( method_exists( $fixture, 'get_start_time' ) ? $fixture->get_start_time() : ( $fixture->start_time ?? null ) ) : null;
        $show_header_date      = ( ! $match_complete && ! empty( $fixture_date ) && empty( $tournament ) );
        $header_play_by        = empty( $start_time );
        $header_formatted_date = ( $show_header_date && ! empty( $fixture_date ) ) ? (string) mysql2date( $this->date_format, (string) $fixture_date ) : null;
        $header_time_display   = null;
        if ( $show_header_date && ! empty( $start_time ) ) {
            $time_str            = ( '00:00' === (string) $start_time ) ? '' : (string) $start_time;
            $header_time_display = ' ' . __( 'at', 'racketmanager' ) . ( '' !== $time_str ? ' ' . $time_str : '' );
        }

        $fixture_location     = $fixture ? ( method_exists( $fixture, 'get_location' ) ? $fixture->get_location() : ( $fixture->location ?? null ) ) : null;
        $show_header_location = ( $location_in_header && ! empty( $fixture_location ) );

        $opponents_vms = [];
        $opponents     = [ 'home', 'away' ];
        $is_walkover   = $fixture ? ( method_exists( $fixture, 'is_walkover' ) ? $fixture->is_walkover() : ( ! empty( $fixture->is_walkover ) ) ) : false;
        $is_retired    = $fixture ? ( method_exists( $fixture, 'is_retired' ) ? $fixture->is_retired() : ( ! empty( $fixture->is_retired ) ) ) : false;

        foreach ( $opponents as $opponent ) {
			$is_opp_winner = ( $winner === $opponent );
			$is_opp_loser  = ( $loser === $opponent );
			$winner_class  = $is_opp_winner ? ' winner' : '';

			$team_dto     = ( 'home' === $opponent ) ? $home_team_dto : $away_team_dto;
			$team         = $team_dto?->team ?? ( is_object( $team_dto ) && ( method_exists( $team_dto, 'get_name' ) || isset( $team_dto->name ) ) ? $team_dto : null );
			$team_players = $team?->players ?? [];

			$titles = [];
			if ( ! $team || empty( $team_players ) ) {
				$match_title = '';
				if ( 'final' === $final ) {
					$prev_title_prop   = 'prev_' . $opponent . '_fixture_title';
					$prev_match_prop   = 'prev_' . $opponent . '_match';
					$prev_fixture_prop = 'prev_' . $opponent . '_fixture';
					$prev_obj          = ( is_object( $fixture ) && isset( $fixture->$prev_match_prop ) ) ? $fixture->$prev_match_prop : ( ( is_object( $fixture ) && isset( $fixture->$prev_fixture_prop ) ) ? $fixture->$prev_fixture_prop : null );
					if ( is_object( $fixture_detail ) && ! empty( $fixture_detail->$prev_title_prop ) ) {
						$match_title = (string) $fixture_detail->$prev_title_prop;
					} elseif ( ! empty( $prev_obj->match_title ) ) {
						$match_title = (string) $prev_obj->match_title;
					} elseif ( ! empty( $prev_obj->title ) ) {
						$match_title = (string) $prev_obj->title;
					} else {
						$match_title = $team ? ( method_exists( $team, 'get_name' ) ? $team->get_name() : ( $team->name ?? '' ) ) : '';
					}
				} else {
					$match_title = $team ? ( method_exists( $team, 'get_name' ) ? $team->get_name() : ( $team->name ?? '' ) ) : '';
				}
				$titles[] = new Tournament_Match_Card_Title_Item_View_Model(
					name: (string) $match_title,
					link: null,
					is_link: false,
					tournament_id: null,
					player_id: null
				);
			} else {
				foreach ( $team_players as $team_player ) {
					$player_name = trim( method_exists( $team_player, 'get_fullname' ) ? $team_player->get_fullname() : ( $team_player->display_name ?? ( $team_player->name ?? '' ) ) );
					$player_id   = method_exists( $team_player, 'get_id' ) ? $team_player->get_id() : ( $team_player->id ?? ( $team_player->ID ?? null ) );
					$player_link = null;
					$is_link     = false;
					$tourn_id    = null;
					if ( ! empty( $tournament ) ) {
						$tourn_name  = $tournament->name ?? ( method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : '' );
						$tourn_id    = $tournament->id ?? ( method_exists( $tournament, 'get_id' ) ? $tournament->get_id() : '' );
						$player_link = '/tournament/' . seo_url( (string) $tourn_name ) . '/player/' . seo_url( $player_name ) . '/';
						$is_link     = true;
					}
					$titles[] = new Tournament_Match_Card_Title_Item_View_Model(
						name: $player_name,
						link: $player_link,
						is_link: $is_link,
						tournament_id: $tourn_id,
						player_id: $player_id
					);
				}
			}

			$show_w_badge    = false;
			$show_l_badge    = false;
			$warning_message = null;

			if ( $is_opp_winner ) {
				if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
					$show_w_badge = true;
				}
			} elseif ( $is_opp_loser ) {
				if ( $is_walkover ) {
					$warning_message = __( 'Walkover', 'racketmanager' );
				} elseif ( $is_retired ) {
					$warning_message = __( 'Retired', 'racketmanager' );
				}
				if ( 'loser' === $player_team_status ) {
					$show_l_badge = true;
				}
			} elseif ( $is_tie ) {
				$warning_message = __( 'Not played', 'racketmanager' );
			}

			$opponents_vms[] = new Tournament_Match_Card_Opponent_View_Model(
				key: $opponent,
				is_winner: $is_opp_winner,
				winner_class: $winner_class,
				titles: $titles,
				show_w_badge: $show_w_badge,
				show_l_badge: $show_l_badge,
				warning_message: $warning_message
			);
		}

		$custom   = $fixture ? ( method_exists( $fixture, 'get_custom' ) ? $fixture->get_custom() : ( $fixture->custom ?? [] ) ) : [];
		$raw_sets = ! empty( $custom['sets'] ) ? $custom['sets'] : [];
		$sets_vms = [];

		foreach ( $raw_sets as $set ) {
			$p1         = '';
			$p2         = '';
			$tb         = '';
			$set_winner = null;

			if ( $set instanceof Set_Score ) {
				$p1         = (string) $set->get_home_games();
				$p2         = (string) $set->get_away_games();
				$tb         = (string) ( $set->get_home_tiebreak() ?? $set->get_away_tiebreak() ?? '' );
				$outcome    = $set->winner_outcome();
				$set_winner = Match_Outcome::HOME === $outcome ? 'player1' : ( Match_Outcome::AWAY === $outcome ? 'player2' : null );
			} elseif ( is_array( $set ) ) {
				$p1         = isset( $set['player1'] ) ? (string) $set['player1'] : '';
				$p2         = isset( $set['player2'] ) ? (string) $set['player2'] : '';
				$tb         = isset( $set['tiebreak'] ) ? (string) $set['tiebreak'] : '';
				$set_winner = $set['winner'] ?? null;
			}

			if ( '' !== $p1 && '' !== $p2 ) {
				$p1_winner_class = ( 'player1' === $set_winner ) ? ' winner' : '';
				$p2_winner_class = ( 'player2' === $set_winner ) ? ' winner' : '';

				$sets_vms[] = new Tournament_Match_Card_Set_View_Model(
					player1_games: $p1,
					player2_games: $p2,
					player1_winner_class: $p1_winner_class,
					player2_winner_class: $p2_winner_class,
					tiebreak: $tb,
					show_p1_tiebreak: ( ! empty( $tb ) && ! empty( $p1_winner_class ) ),
					show_p2_tiebreak: ( ! empty( $tb ) && ! empty( $p2_winner_class ) )
				);
			}
		}

		$is_confirmed       = $fixture ? ( method_exists( $fixture, 'get_confirmed' ) ? $fixture->get_confirmed() : ( $fixture->confirmed ?? null ) ) : null;
		$show_update_button = ( $user_can_update && empty( $is_confirmed ) );
		$pencil_icon_url    = defined( 'RACKETMANAGER_URL' ) ? RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' : '/wp-content/plugins/racketmanager/assets/icons/bootstrap-icons.svg#pencil';

		$footer_location = null;
		if ( empty( $fixture_location ) ) {
			$fixture_host = $fixture ? ( method_exists( $fixture, 'get_host' ) ? $fixture->get_host() : ( $fixture->host ?? null ) ) : null;
			if ( isset( $fixture_host ) ) {
				if ( 'home' === $fixture_host && isset( $home_team_dto->club->shortcode ) ) {
					$footer_location = (string) $home_team_dto->club->shortcode;
				} elseif ( 'away' === $fixture_host && isset( $away_team_dto->club->shortcode ) ) {
					$footer_location = (string) $away_team_dto->club->shortcode;
				}
			}
		} else {
			$footer_location = (string) $fixture_location;
		}

		$show_footer_date      = ( ! $match_complete && ! empty( $tournament ) && empty( $match_display ) );
		$footer_play_by        = empty( $start_time );
		$footer_formatted_date = ( $show_footer_date && ! empty( $fixture_date ) ) ? (string) mysql2date( $this->date_format, (string) $fixture_date ) : null;
		$footer_time_display   = null;
		if ( $show_footer_date && ! empty( $start_time ) ) {
			$time_str            = ( '00:00' === (string) $start_time ) ? '' : (string) $start_time;
			$footer_time_display = ' ' . __( 'at', 'racketmanager' ) . ( '' !== $time_str ? ' ' . $time_str : '' );
		}

		return new Tournament_Match_Card_View_Model(
			id: $fixture ? ( method_exists( $fixture, 'get_id' ) ? $fixture->get_id() : ( $fixture->id ?? null ) ) : null,
			match_display_class: $match_display_class,
			is_selected: $match_selected,
			selected_class: $match_selected ? 'is-selected' : '',
			final_name: $final_name,
			draw_link: $draw_link,
			league_title: (string) $league_title,
			show_draw_link: $show_draw_link,
			show_header_date: $show_header_date,
			header_play_by: $header_play_by,
			header_formatted_date: $header_formatted_date,
			header_time_display: $header_time_display,
			show_header_location: $show_header_location,
			location: $fixture_location ? (string) $fixture_location : null,
			opponents: $opponents_vms,
			sets: $sets_vms,
			show_update_button: $show_update_button,
			update_link: $match_link,
			pencil_icon_url: $pencil_icon_url,
			footer_location: $footer_location,
			show_footer_date: $show_footer_date,
			footer_play_by: $footer_play_by,
			footer_formatted_date: $footer_formatted_date,
			footer_time_display: $footer_time_display
		);
    }

    /**
     * Present winners grouped by category for tournament winners view.
     *
     * @param array $winners
     * @param object $tournament
     * @return array
     */
    public function present_tournament_winners( array $winners, object $tournament ): array {
        $presented  = [];
        $tourn_name = method_exists( $tournament, 'get_name' ) ? $tournament->get_name() : ( $tournament->name ?? '' );
        $tourn_id   = method_exists( $tournament, 'get_id' ) ? $tournament->get_id() : ( $tournament->id ?? 0 );
        $tourn_slug = Util::seo_url( (string) $tourn_name );

        foreach ( $winners as $key => $winner_group ) {
            $presented_group = [];
            foreach ( $winner_group as $winner ) {
                $w = is_object( $winner ) ? clone $winner : (object) $winner;
                $event_name        = $w->event_name ?? '';
                $w->link_url       = '/tournament/' . $tourn_slug . '/draw/' . Util::seo_url( (string) $event_name ) . '/';
                $w->link_class     = 'tabDataLink';
                $w->data_season    = null;
                $w->data_type      = 'tournament';
                $w->data_type_id   = $tourn_id;
                $w->data_type_type = 'draws';
                $presented_group[] = $w;
            }
            $presented[ $key ] = $presented_group;
        }

        return $presented;
    }

    /**
     * Present tournament draw leagues with precomputed match URLs, opponent details, and score models.
     *
     * @param object $draw Event/Draw object
     * @param object $tournament Tournament object
     * @return object
     */
    public function present_tournament_draw( object $draw, object $tournament ): object {
        $presented_draw = clone $draw;
        if ( ! empty( $presented_draw->leagues ) && is_array( $presented_draw->leagues ) ) {
            $presented_leagues = [];
            foreach ( $presented_draw->leagues as $l_idx => $league ) {
                $league_obj = is_object( $league ) ? clone $league : (object) $league;
                if ( ! empty( $league_obj->finals ) && is_array( $league_obj->finals ) ) {
                    $league_obj->finals = Draw_Presenter::present_finals(
                        $league_obj->finals,
                        $tournament,
                        $league_obj,
                        $league_obj->event->competition ?? ( $presented_draw->competition ?? null ),
                        $presented_draw
                    );
                }
                $presented_leagues[ $l_idx ] = $league_obj;
            }
            $presented_draw->leagues = $presented_leagues;
        }

        return $presented_draw;
    }

    private function format( ?string $date, string $fallback ): string {
        if ( empty( $date ) || '0000-00-00' === $date ) {
            return $fallback;
        }
        $formatted = mysql2date( $this->date_format, $date );
        if ( false === $formatted || '' === $formatted ) {
            return $fallback;
        }
        return (string) $formatted;
    }
}
