<?php
/**
 * Admin league matches presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Admin_League_Match_View_Model;

/**
 * Maps league matches to preformatted admin league match row view models.
 */
final class Admin_League_Matches_Presenter {

    public function __construct(
        private Admin_Date_Presenter $date_presenter,
    ) {
    }

    /**
     * @param object[] $matches
     * @param object   $league
     * @param string   $season
     * @param string   $group
     *
     * @return Admin_League_Match_View_Model[]
     */
    public function present_collection( array $matches, object $league, string $season, string $group = '' ): array {
        $presented = array();
        foreach ( $matches as $match ) {
            if ( ! is_object( $match ) ) {
                continue;
            }
            $presented[] = $this->present( $match, $league, $season, $group );
        }

        return $presented;
    }

    public function present( object $match, object $league, string $season, string $group = '' ): Admin_League_Match_View_Model {
        $formatted_date = $this->date_presenter->format_date( isset( $match->date ) ? (string) $match->date : null );

        $hour_min       = ( $match->hour ?? '' ) . ':' . ( $match->minutes ?? '' );
        $formatted_time = ( '00:00' === $hour_min || ! isset( $match->hour ) || '' === $match->hour )
            ? 'N/A'
            : $this->date_presenter->format_time( isset( $match->date ) ? (string) $match->date : null );

        $competition_type = '';
        if ( isset( $league->event->competition->type ) ) {
            $type             = $league->event->competition->type;
            $competition_type = $type instanceof \BackedEnum ? $type->value : (string) $type;
        }

        $league_id = (string) ( $league->id ?? '' );
        $match_id  = (string) ( $match->id ?? '' );

        $edit_url = sprintf(
            '/wp-admin/admin.php?page=racketmanager-%ss&view=match&league_id=%s&edit=%s&season=%s%s',
            rawurlencode( $competition_type ),
            rawurlencode( $league_id ),
            rawurlencode( $match_id ),
            rawurlencode( $season ),
            empty( $group ) ? '' : '&group=' . rawurlencode( $group )
        );

        return new Admin_League_Match_View_Model(
            match: $match,
            formatted_date: $formatted_date,
            formatted_time: $formatted_time,
            edit_url: $edit_url,
            formatted_location: empty( $match->location ) ? 'N/A' : (string) $match->location,
            id: isset( $match->id ) ? (int) $match->id : null,
            home_team: $match->home_team ?? null,
            away_team: $match->away_team ?? null,
            group: isset( $match->group ) ? (string) $match->group : null,
            match_title: isset( $match->match_title ) ? (string) $match->match_title : null,
            custom: $match->custom ?? null,
            home_points: $match->home_points ?? null,
            away_points: $match->away_points ?? null,
            hour: isset( $match->hour ) ? (string) $match->hour : null,
            minutes: isset( $match->minutes ) ? (string) $match->minutes : null,
            date: $match->date ?? null,
            location: isset( $match->location ) ? (string) $match->location : null,
        );
    }
}
