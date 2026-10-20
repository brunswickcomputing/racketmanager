<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Favourite_Controls_View_Model;
use Racketmanager\Presentation\View_Models\Favourite_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Favourite_State_View_Model;
use Racketmanager\Util\Util;
use function __;
use function defined;
use function is_int;
use function is_object;
use function is_string;
use function trim;

final readonly class Favourite_Presenter {
    public function __construct( private User_Favourites_Gateway_Interface $gateway ) {}

    /** Shared view data for all public shortcode entry points, including theme overrides. */
    public function present_template_vars( array $vars ): array {
        $presentation = [ 'favourite_controls' => $this->present_controls() ];
        if ( isset( $vars['favourite_types'] ) ) {
            $presentation['favourite_rows_by_type'] = [];
            foreach ( $vars['favourite_types'] as $group ) {
                $type = $group['name'];
                $presentation['favourite_rows_by_type'][ $type ] = $this->present_list( $type, $group['favourites'] );
            }
        }
        if ( isset( $vars['favourites'], $vars['favourite_name'] ) ) {
            $presentation['favourite_rows'] = $this->present_list( $vars['favourite_name'], $vars['favourites'] );
        }
        return $presentation;
    }

    public function present_controls(): Favourite_Controls_View_Model {
        $favourites     = $this->gateway->get_current_user_favourites();
        $unselected     = new Favourite_State_View_Model( __( 'Add favourite', 'racketmanager' ), '', '', '' );
        $selected_state = new Favourite_State_View_Model( __( 'Remove favourite', 'racketmanager' ), '1', 'is-favourite', 'fav-icon-svg-selected' );
        $selected       = [];
        foreach ( $favourites ?? [] as $type => $ids ) {
            foreach ( $ids as $id ) {
                if ( is_int( $id ) || is_string( $id ) ) {
                    $selected[ $type ][ $id ] = $selected_state;
                }
            }
        }
        return new Favourite_Controls_View_Model( null !== $favourites, $selected, $unselected );
    }

    /**
     * @param list<object> $favourites
     * @return list<Favourite_List_Item_View_Model>
     */
    public function present_list( string $type, array $favourites ): array {
        $rows = [];
        foreach ( $favourites as $favourite ) {
            if ( ! is_object( $favourite ) ) {
                continue;
            }
            $detail = $favourite->detail ?? null;
            $name   = (string) ( $favourite->name ?? ( is_object( $detail ) ? ( $detail->title ?? $detail->name ?? $detail->display_name ?? '' ) : '' ) );
            if ( '' === trim( $name ) ) {
                $name = __( 'Unavailable', 'racketmanager' );
            }
            $id     = (int) ( $favourite->id ?? ( is_object( $detail ) ? ( $detail->id ?? $detail->ID ?? 0 ) : 0 ) );

            [ $url, $icon ] = match ( $type ) {
                'league' => [
                    ( is_object( $detail ) && ! empty( $detail->title ) && is_string( $detail->title ) )
                        ? '/league/' . Util::seo_url( $detail->title ) . '/'
                        : '',
                    'assets/icons/bootstrap-icons.svg#table',
                ],
                'club' => [
                    ( is_object( $detail ) && ! empty( $detail->shortcode ) && is_string( $detail->shortcode ) )
                        ? '/clubs/' . Util::seo_url( $detail->shortcode ) . '/'
                        : '',
                    'assets/icons/lta-icons-extra.svg#icon-team',
                ],
                'team' => [
                    $this->resolve_team_url( $favourite, $detail ),
                    'assets/icons/lta-icons-extra.svg#icon-team',
                ],
                'competition' => [
                    $this->resolve_competition_url( $favourite, $detail ),
                    'assets/icons/lta-icons.svg#icon-bracket',
                ],
                'player' => [
                    ( is_object( $detail ) && ! empty( $detail->display_name ) && is_string( $detail->display_name ) )
                        ? '/player/' . Util::seo_url( $detail->display_name ) . '/'
                        : '',
                    'assets/icons/bootstrap-icons.svg#person-fill',
                ],
                default => [ '', '' ],
            };

            $icon_url = ( '' !== $icon && defined( 'RACKETMANAGER_URL' ) ) ? RACKETMANAGER_URL . $icon : '';

            $rows[] = new Favourite_List_Item_View_Model(
                $id,
                $type,
                $name,
                $url,
                $icon_url,
            );
        }
        return $rows;
    }

    private function resolve_team_url( object $favourite, ?object $detail ): string {
        $club_shortcode = $favourite->club_shortcode ?? null;
        if ( ! is_string( $club_shortcode ) || '' === $club_shortcode ) {
            if ( is_object( $detail ) ) {
                if ( isset( $detail->club ) && is_object( $detail->club ) && ! empty( $detail->club->shortcode ) && is_string( $detail->club->shortcode ) ) {
                    $club_shortcode = $detail->club->shortcode;
                } elseif ( isset( $detail->club_shortcode ) && is_string( $detail->club_shortcode ) ) {
                    $club_shortcode = $detail->club_shortcode;
                }
            }
        }

        if ( is_string( $club_shortcode ) && '' !== trim( $club_shortcode ) ) {
            return '/clubs/' . Util::seo_url( trim( $club_shortcode ) ) . '/competitions/';
        }

        return '';
    }

    private function resolve_competition_url( object $favourite, ?object $detail ): string {
        if ( ! is_object( $detail ) ) {
            return '';
        }

        $competition_name = null;
        if ( isset( $detail->competition ) ) {
            if ( is_object( $detail->competition ) && ! empty( $detail->competition->name ) && is_string( $detail->competition->name ) ) {
                $competition_name = $detail->competition->name;
            } elseif ( is_string( $detail->competition ) ) {
                $competition_name = $detail->competition;
            }
        }

        $event_name = ( isset( $detail->name ) && is_string( $detail->name ) )
            ? $detail->name
            : ( ( isset( $favourite->name ) && is_string( $favourite->name ) ) ? $favourite->name : null );

        if ( is_string( $competition_name ) && '' !== $competition_name && is_string( $event_name ) && '' !== $event_name ) {
            return '/tournaments/' . Util::seo_url( $competition_name ) . '/' . Util::seo_url( $event_name ) . '/';
        }

        return '';
    }
}