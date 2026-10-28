<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\League_Archive_View_Model;
use Racketmanager\Util\Util;
use function __;
use function array_reverse;
use function function_exists;
use function get_permalink;
use function get_the_ID;
use function is_array;
use function is_object;
use function mysql2date;
use function str_starts_with;

/**
 * Presenter for formatting league archive data and header presentation models.
 */
class League_Archive_Presenter {
    public function __construct(
        private readonly ?Option_Gateway_Interface $option_gateway = null
    ) {}

    public function present_archive(
        object $league,
        object $event,
        array $seasons,
        array $leagues,
        string $tab,
        int|string|null $post_id = null
    ): League_Archive_View_Model {
        $competition_type = ( isset( $event->competition->type ) && $event->competition->type instanceof BackedEnum )
            ? $event->competition->type->value
            : (string) ( $event->competition->type ?? 'league' );

        $league_id       = $league->id ?? 0;
        $league_title    = (string) ( $league->title ?? '' );
        $league_seo_name = Util::seo_url( $league_title );
        $pagename        = '/' . $competition_type . '/' . $league_seo_name . '/';

        $icon_path = match ( $competition_type ) {
            'league'     => 'assets/icons/bootstrap-icons.svg#table',
            'cup'        => 'assets/icons/bootstrap-icons.svg#trophy-fill',
            'tournament' => 'assets/icons/lta-icons.svg#icon-bracket',
            default      => 'assets/icons/bootstrap-icons.svg#table',
        };
        $icon_url          = ( defined( 'RACKETMANAGER_URL' ) ? RACKETMANAGER_URL : '' ) . $icon_path;
        $calendar_icon_url = ( defined( 'RACKETMANAGER_URL' ) ? RACKETMANAGER_URL : '' ) . 'assets/icons/bootstrap-icons.svg#calendar-range-fill';

        $event_name          = (string) ( $event->name ?? '' );
        $current_season_name = (string) ( $league->current_season['name'] ?? '' );

        $event_url = '' !== $event_name
            ? '/' . Util::seo_url( $competition_type ) . 's/' . Util::seo_url( $event_name ) . '/' . $current_season_name . '/'
            : '';

        $competition_name = (string) ( $event->competition->name ?? '' );
        $competition_url  = '' !== $competition_name
            ? '/' . Util::seo_url( $competition_name ) . '/' . $current_season_name . '/'
            : '';

        $competition_season = [];
        if ( isset( $event->competition ) && is_object( $event->competition ) && '' !== $current_season_name ) {
            if ( method_exists( $event->competition, 'get_season_by_name' ) ) {
                $competition_season = $event->competition->get_season_by_name( $current_season_name ) ?? [];
            } elseif ( method_exists( $event->competition, 'get_seasons' ) ) {
                $competition_seasons = $event->competition->get_seasons();
                $competition_season  = is_array( $competition_seasons ) ? ( $competition_seasons[ $current_season_name ] ?? [] ) : [];
            }
        }

        $range              = Date_Range::try_create(
            $competition_season['date_start'] ?? null,
            $competition_season['date_end'] ?? null
        );
        $has_date_range     = null !== $range;
        $date_start_display = $has_date_range ? $this->format_date( $range->get_start_string() ) : null;
        $date_end_display   = $has_date_range ? $this->format_date( $range->get_end_string() ) : null;

        $resolved_post_id = $post_id ?: ( function_exists( 'get_the_ID' ) && false !== get_the_ID() ? (int) get_the_ID() : '' );
        $form_action      = ( '' !== (string) $resolved_post_id && function_exists( 'get_permalink' ) )
            ? (string) get_permalink( (int) $resolved_post_id )
            : '';

        $is_box           = (bool) ( $event->is_box ?? false );
        $season_title     = $is_box ? __( 'Round', 'racketmanager' ) : __( 'Season', 'racketmanager' );
        $season_selection = $is_box ? __( 'Rounds', 'racketmanager' ) : __( 'Seasons', 'racketmanager' );

        $season_options = [];
        foreach ( array_reverse( $seasons ) as $season ) {
            $s_name = is_array( $season ) ? ( $season['name'] ?? '' ) : ( (string) $season );
            if ( '' === $s_name ) {
                continue;
            }
            $s_label          = ( $is_box ? $season_title . ' - ' : '' ) . $s_name;
            $season_options[] = [
                'name'     => $s_name,
                'label'    => $s_label,
                'selected' => ( $s_name === $current_season_name ),
            ];
        }

        $league_options = [];
        foreach ( $leagues as $l ) {
            $l_id             = is_object( $l ) ? ( $l->id ?? 0 ) : ( $l['id'] ?? 0 );
            $l_title          = is_object( $l ) ? ( $l->title ?? '' ) : ( $l['title'] ?? '' );
            $league_options[] = [
                'id'       => $l_id,
                'title'    => (string) $l_title,
                'slug'     => Util::seo_url( (string) $l_title ),
                'selected' => ( $l_id === $league_id ),
            ];
        }

        $is_team_entry = (bool) ( $event->competition->is_team_entry ?? false );
        $menu_options  = [
            'standings'  => [
                'name'        => 'standings',
                'selected'    => 'standings' === $tab,
                'available'   => true,
                'description' => __( 'Standings', 'racketmanager' ),
            ],
            'crosstable' => [
                'name'        => 'crosstable',
                'selected'    => 'crosstable' === $tab,
                'available'   => true,
                'description' => __( 'Crosstable', 'racketmanager' ),
            ],
            'matches'    => [
                'name'        => 'matches',
                'selected'    => 'matches' === $tab,
                'available'   => true,
                'description' => __( 'Matches', 'racketmanager' ),
            ],
            'teams'      => [
                'name'        => 'teams',
                'selected'    => 'teams' === $tab,
                'available'   => true,
                'description' => __( 'Teams', 'racketmanager' ),
            ],
        ];

        if ( $is_team_entry ) {
            $menu_options['players'] = [
                'name'        => 'players',
                'selected'    => 'players' === $tab,
                'available'   => true,
                'description' => __( 'Players', 'racketmanager' ),
            ];
        }

        $is_championship     = (bool) ( $event->competition->is_championship ?? false );
        $championship_season = (string) ( $league->season ?? '' );

        return new League_Archive_View_Model(
            league_id: $league_id,
            league_title: $league_title,
            league_seo_name: $league_seo_name,
            competition_type: $competition_type,
            icon_path: $icon_path,
            icon_url: $icon_url,
            calendar_icon_url: $calendar_icon_url,
            event_name: $event_name,
            event_url: $event_url,
            competition_name: $competition_name,
            competition_url: $competition_url,
            current_season_name: $current_season_name,
            date_start_display: $date_start_display,
            date_end_display: $date_end_display,
            has_date_range: $has_date_range,
            post_id: $resolved_post_id,
            pagename: $pagename,
            form_action: $form_action,
            season_title: $season_title,
            season_selection: $season_selection,
            seasons: $seasons,
            season_options: $season_options,
            league_options: $league_options,
            favourite_id: $league_id,
            favourite_type: 'league',
            is_championship: $is_championship,
            championship_season: $championship_season,
            menu_options: $menu_options,
            tab: $tab,
            templates: is_object( $league ) && isset( $league->templates ) && is_array( $league->templates ) ? $league->templates : [],
        );
    }

    public function format_date( ?string $date, string $fallback = '' ): string {
        if ( empty( $date ) || str_starts_with( (string) $date, '0000-00-00' ) ) {
            return $fallback;
        }

        $date_format = $this->option_gateway?->get_option( 'date_format', 'j M Y' ) ?? 'j M Y';
        if ( function_exists( 'mysql2date' ) ) {
            $formatted = mysql2date( $date_format, $date );
            if ( false !== $formatted && '' !== $formatted ) {
                return (string) $formatted;
            }
        }

        return $fallback;
    }
}
