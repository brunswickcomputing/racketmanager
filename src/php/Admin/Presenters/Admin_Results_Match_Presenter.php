<?php
/**
 * Admin results match presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

/**
 * Maps match entities to preformatted admin results rows.
 */
final class Admin_Results_Match_Presenter {
    /** @var array<int, ?object> */
    private array $league_cache = array();

    public function __construct(
        private Admin_Date_Presenter $date_presenter,
        private League_Repository_Interface $league_repository,
    ) {
    }

    /**
     * @param object[] $matches Match-like objects.
     *
     * @return Admin_Results_Match_View_Model[]
     */
    public function present_collection( array $matches, string $referrer, bool $include_result_path = true, string $default_tooltip = '', mixed $result_pending = null ): array {
        $rows = array();
        foreach ( $matches as $match ) {
            if ( ! is_object( $match ) ) {
                continue;
            }
            $rows[] = $this->present( $match, $referrer, $include_result_path, $default_tooltip, $result_pending );
        }

        return $rows;
    }

    public function present( object $match, string $referrer, bool $include_result_path = true, string $default_tooltip = '', mixed $result_pending = null ): Admin_Results_Match_View_Model {
        $league = $this->resolve_league( $match );

        return new Admin_Results_Match_View_Model(
            league_id: (int) ( $match->league_id ?? ( is_object( $league ) ? ( $league->id ?? 0 ) : 0 ) ),
            league_title: $this->league_title( $league ),
            formatted_date: $this->date_presenter->format_date( isset( $match->date ) ? (string) $match->date : null, 'Y-m-d' ),
            match_title: (string) ( $match->match_title ?? $match->fixture_title ?? '' ),
            match_link: $this->build_link( (string) ( $match->link ?? '' ), $referrer, false ),
            result_link: $this->build_link( (string) ( $match->link ?? '' ), $referrer, $include_result_path ),
            confirmed_display: (string) ( $match->confirmed_display ?? '' ),
            score: (string) ( $match->score ?? '' ),
            tooltip: $this->tooltip( $match, $default_tooltip, $result_pending ),
        );
    }

    private function resolve_league( object $match ): ?object {
        if ( isset( $match->league ) && is_object( $match->league ) ) {
            return $match->league;
        }
        if ( method_exists( $match, 'get_league' ) ) {
            $league = $match->get_league();
            if ( is_object( $league ) ) {
                return $league;
            }
        }
        $league_id = (int) ( $match->league_id ?? 0 );
        if ( $league_id > 0 ) {
            if ( ! array_key_exists( $league_id, $this->league_cache ) ) {
                $found                           = $this->league_repository->find_by_id( $league_id );
                $this->league_cache[ $league_id ] = is_object( $found ) ? $found : null;
            }
            return $this->league_cache[ $league_id ];
        }

        return null;
    }

    private function league_title( ?object $league ): string {
        if ( ! $league ) {
            return '';
        }

        return (string) ( $league->title ?? $league->name ?? '' );
    }

    private function build_link( string $link, string $referrer, bool $include_result_path ): string {
        if ( '' === $link ) {
            return '';
        }

        $url = $include_result_path ? rtrim( $link, '/' ) . '/result/' : $link;
        if ( function_exists( 'add_query_arg' ) ) {
            return add_query_arg( 'referrer', $referrer, $url );
        }

        $separator = str_contains( $url, '?' ) ? '&' : '?';

        return $url . $separator . 'referrer=' . rawurlencode( $referrer );
    }

    private function tooltip( object $match, string $default_tooltip, mixed $result_pending ): string {
        $tooltip = $default_tooltip;
        if ( empty( $result_pending ) ) {
            return $tooltip;
        }

        $overdue_date = $match->result_overdue_date ?? null;
        if ( empty( $overdue_date ) ) {
            return $tooltip;
        }

        $now          = date_create();
        $date_overdue = date_create( (string) $overdue_date );
        if ( $now && $date_overdue && $date_overdue < $now ) {
            $overdue_time = $match->overdue_time ?? 0;
            /* translators: %d: days overdue */
            $tooltip = sprintf( __( 'Result overdue by %d days', 'racketmanager' ), (int) ceil( (float) $overdue_time ) );
        }

        return $tooltip;
    }
}
