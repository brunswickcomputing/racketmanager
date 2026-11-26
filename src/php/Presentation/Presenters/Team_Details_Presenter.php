<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Team_Details_View_Model;
use Racketmanager\Presentation\View_Models\Team_Row_View_Model;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;
use function add_query_arg;
use function esc_attr;
use function explode;
use function is_iterable;
use function is_object;
use function method_exists;
use function mysql2date;
use function rest_url;
use function substr;

/**
 * Presenter responsible for building the immutable Team_Details_View_Model.
 */
final readonly class Team_Details_Presenter {
    /**
     * Initialise the service with option storage and settings access dependencies.
     *
     * @param Option_Gateway_Interface $option_gateway Option storage gateway.
     * @param Settings_Service         $settings_service Settings service.
     *
     * @return void
     */
    public function __construct(
        private Option_Gateway_Interface $option_gateway,
        private Settings_Service $settings_service,
    ) {
    }

    /**
     * Build immutable Team_Details_View_Model from domain objects.
     *
     * @param object      $target_object League or Event object.
     * @param object|null $team_details Team details DTO or object.
     * @param bool        $user_can_edit_team Whether the current user may edit this team.
     *
     * @return Team_Details_View_Model
     */
    public function present( object $target_object, ?object $team_details, bool $user_can_edit_team = false ): Team_Details_View_Model {
        $display_options = $this->settings_service->get_category( 'display' );
        $show_wtn        = ! empty( $display_options['wtn'] );
        $format          = null;
        $help_text       = null;

        if ( isset( $target_object->competition ) ) {
            $object_competition = $target_object->competition;
            $object_event       = $target_object;
        } else {
            $object_competition = $target_object->event?->competition ?? null;
            $object_event       = $target_object->event ?? $target_object;
        }

        if ( $show_wtn ) {
            $help_text  = __( 'World Tennis Number', 'racketmanager' );
            $event_type = $object_event?->type ?? '';
            $format     = substr( (string) $event_type, 1, 1 );
            if ( 'S' !== $format && 'D' !== $format ) {
                $format = 'S';
            }
            $format_type = Util_Lookup::get_match_type( $format );
            if ( $format_type ) {
                $help_text = $format_type . ' ' . $help_text;
            }
        }

        $time_format          = (string) $this->option_gateway->get_option( 'time_format', 'H:i' );
        $team                 = $team_details?->team ?? ( $target_object->team ?? null );
        $match_time           = $team?->info?->match_time ?? null;
        $match_time_formatted = null;
        if ( ! empty( $match_time ) && '00:00:00' !== $match_time ) {
            $match_time_formatted = mysql2date( $time_format, $match_time );
        }

        $comp_type = $object_competition?->type instanceof BackedEnum
            ? $object_competition->type->value
            : (string) ( $object_competition?->type ?? '' );

        $season_name    = $target_object->current_season['name'] ?? '';
        $object_name    = $target_object->title ?? $target_object->name ?? '';
        $standings_link = '/' . $comp_type . '/' . Util::seo_url( $object_name ) . '/' . $season_name . '/';

        $club_link = ! empty( $team_details?->club?->shortcode )
            ? '/clubs/' . Util::seo_url( $team_details->club->shortcode ) . '/'
            : '';

        $player_links = [];
        $players      = $team?->players ?? [];
        if ( is_iterable( $players ) ) {
            foreach ( $players as $player ) {
                $player_id   = method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? $player->ID ?? null );
                $player_name = method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->name ?? '' );
                if ( null !== $player_id ) {
                    $player_links[ $player_id ] = '/' . $comp_type . '/' . Util::seo_url( $object_name ) . '/' . $season_name . '/player/' . Util::seo_url( $player_name ) . '/';
                }
            }
        }

        $calendar_url = $team && ! empty( $target_object->id ) ? add_query_arg(
            [
                'league_id' => $target_object->id,
                'team_id'   => $team->get_id(),
                'season'    => $season_name,
            ],
            rest_url( 'racketmanager/v1/export/calendar' )
        ) : '';

        return new Team_Details_View_Model(
            show_wtn: $show_wtn,
            format: $format,
            help_text: $help_text,
            standings_link: $standings_link,
            club_link: $club_link,
            player_links: $player_links,
            match_time_formatted: $match_time_formatted,
            user_can_edit_team: $user_can_edit_team,
            calendar_url: $calendar_url,
        );
    }

    /**
     * Builds a presentation row view model for a team.
     *
     * @param object      $team Team object or existing team row view model to present.
     * @param object|null $target_object Optional related league or event object used to determine context and entry settings.
     * @param string|null $item_link Optional base item link used to build the team link.
     * @param array|null  $team_links Optional map of team IDs to team-specific links.
     *
     * @return Team_Row_View_Model Presented team row view model.
     */
    public function present_team_row(
        object $team,
        ?object $target_object = null,
        ?string $item_link = null,
        ?array $team_links = null
    ): Team_Row_View_Model {
        if ( $team instanceof Team_Row_View_Model ) {
            return $team;
        }

        if ( isset( $target_object->event->competition ) ) {
            $is_team_entry   = (bool) ( $target_object->event->competition->is_team_entry ?? false );
            $is_player_entry = (bool) ( $target_object->event->competition->is_player_entry ?? false );
            $object_type     = 'league';
            $event_id        = isset( $target_object->event->id ) ? (int) $target_object->event->id : null;
            $is_championship = (bool) ( $target_object->is_championship ?? false );
            $league_id       = $target_object->id ?? null;
            $season_name     = $target_object->current_season['name'] ?? '';
        } elseif ( isset( $target_object->competition ) ) {
            $is_team_entry   = (bool) ( $target_object->competition->is_team_entry ?? false );
            $is_player_entry = (bool) ( $target_object->competition->is_player_entry ?? false );
            $object_type     = 'event';
            $event_id        = isset( $target_object->id ) ? (int) $target_object->id : null;
            $is_championship = (bool) ( $target_object->competition->is_championship ?? false );
            $league_id       = null;
            $season_name     = $target_object->current_season['name'] ?? '';
        } else {
            $is_team_entry   = false;
            $is_player_entry = false;
            $object_type     = null;
            $event_id        = null;
            $is_championship = false;
            $league_id       = null;
            $season_name     = '';
        }

        $team_id    = method_exists( $team, 'get_id' ) ? $team->get_id() : ( $team->id ?? 0 );
        $team_title = method_exists( $team, 'get_name' ) ? $team->get_name() : ( $team->title ?? $team->name ?? '' );

        if ( empty( $item_link ) ) {
            $team_link  = '#';
            $data_attrs = 'data-action="open-team-edit-modal" data-team-id="' . esc_attr( (string) $team_id ) . '" data-event-id="' . esc_attr( (string) ( $event_id ?? '' ) ) . '"';
        } else {
            $team_link  = $team->link ?? ( $team_links[ $team_id ] ?? ( $item_link . '/team/' . esc_attr( Util::seo_url( (string) $team_title ) ) . '/' ) );
            $data_attrs = 'class="nav--link tabDataLink" data-type="' . esc_attr( (string) $object_type ) . '" data-type-id="' . esc_attr( (string) ( $target_object->id ?? '' ) ) . '" data-season="' . esc_attr( (string) $season_name ) . '" data-link="' . esc_attr( $team_link ) . '" data-link-id="' . esc_attr( (string) $team_id ) . '" data-link-type="teams"';
        }

        $words    = explode( ' ', (string) $team_title );
        $initials = '';
        foreach ( $words as $w ) {
            if ( '' !== $w ) {
                $initials .= $w[0];
            }
        }

        $suffix = empty( $event_id ) ? null : '-' . $event_id . '-' . $team_id;

        $calendar_url = null;
        if ( ! empty( $league_id ) && ! $is_championship ) {
            $calendar_url = add_query_arg(
                [
                    'league_id' => $league_id,
                    'team_id'   => $team_id,
                    'season'    => $season_name,
                ],
                rest_url( 'racketmanager/v1/export/calendar' )
            );
        }

        $info                 = $team->info ?? null;
        $captain_name         = $info->captain ?? null;
        $captain_contactno    = $info->captain_contactno ?? null;
        $captain_contactemail = $info->captain_contactemail ?? null;
        $match_day            = $info->match_day ?? null;
        $match_time           = $info->match_time ?? null;

        $match_time_formatted = null;
        if ( ! empty( $match_time ) && '00:00:00' !== $match_time ) {
            $match_time_formatted = substr( (string) $match_time, 0, 5 );
        }

        $players = [];
        if ( ! empty( $info->players ) && is_iterable( $info->players ) ) {
            foreach ( $info->players as $p ) {
                $players[] = [
                    'display_name' => $p->display_name ?? $p->name ?? '',
                    'contactno'    => $p->contactno ?? null,
                    'email'        => $p->email ?? null,
                ];
            }
        }

        return new Team_Row_View_Model(
            id: $team_id,
            title: (string) $team_title,
            initials: $initials,
            link: $team_link,
            data_attrs: $data_attrs,
            event_id: $event_id,
            suffix: $suffix,
            calendar_url: $calendar_url,
            is_team_entry: $is_team_entry,
            is_player_entry: $is_player_entry,
            captain_name: $captain_name,
            captain_contactno: $captain_contactno,
            captain_contactemail: $captain_contactemail,
            match_day: $match_day,
            match_time: $match_time,
            match_time_formatted: $match_time_formatted,
            players: $players,
            raw_team: $team,
        );
    }

    /**
     * Builds presentation rows for the provided team objects.
     *
     * @param array       $teams List of teams to present.
     * @param object|null $target_object Optional related object used when presenting each team row.
     * @param string|null $item_link Optional item link used when presenting each team row.
     * @param array|null  $team_links Optional team-specific links used when presenting each team row.
     *
     * @return array Presented team rows.
     */
    public function present_team_rows(
        array $teams,
        ?object $target_object = null,
        ?string $item_link = null,
        ?array $team_links = null
    ): array {
        $rows = [];
        foreach ( $teams as $team ) {
            if ( is_object( $team ) ) {
                $rows[] = $this->present_team_row( $team, $target_object, $item_link, $team_links );
            }
        }
        return $rows;
    }
}
