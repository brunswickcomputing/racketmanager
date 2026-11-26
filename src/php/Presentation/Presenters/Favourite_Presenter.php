<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface;
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

/**
 * Converts favourite data into view models used by templates.
 *
 * Builds control state for the current user's favourites, transforms favourite records into list item
 * view models, and resolves display metadata such as labels, URLs, and icon paths for supported
 * favourite types.
 */
final readonly class Favourite_Presenter {
    /**
     * Initialises the service with a user favourites gateway.
     *
     * @param User_Favourites_Gateway_Interface $gateway Gateway used to access user favourites data.
     *
     * @return void
     */
    public function __construct( private User_Favourites_Gateway_Interface $gateway ) {}

    /**
     * Builds presentation variables for favourite controls and favourite rows.
     *
     * @param array $vars Template variables containing favourite type groups or a single favourite list.
     *
     * @return array Presentation data including favourite controls and any generated favourite rows.
     */
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

    /**
     * Builds the favourite controls view model for the current user.
     *
     * Retrieves the current user's favourites, maps valid favourite identifiers to the selected state,
     * and provides the default unselected state for items that are not marked as favourites.
     *
     * @return Favourite_Controls_View_Model View model containing favourite availability, selected states, and the unselected state.
     */
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
     * Builds view model rows for a list of favourites of the specified type.
     *
     * @param string $type Favourite type used to resolve URLs and icons.
     * @param array  $favourites Favourite records to transform into list item view models.
     *
     * @return array List of favourite list item view models.
     */
    public function present_list( string $type, array $favourites ): array {
        $rows = [];
        foreach ( $favourites as $favourite ) {
            if ( ! is_object( $favourite ) ) {
                continue;
            }
            $detail = $favourite->detail ?? null;
            $name   = $favourite->name ?? ( is_object( $detail ) ? ( $detail->title ?? $detail->name ?? $detail->display_name ?? '' ) : '' );
            if ( '' === trim( $name ) ) {
                $name = __( 'Unavailable', 'racketmanager' );
            }
            $id = (int) $favourite->id ?? ( is_object( $detail ) ? ( $detail->id ?? $detail->ID ?? 0 ) : 0 );

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

    /**
     * Resolves the competitions URL for a team based on the available club shortcode.
     *
     * @param object      $favourite Favourite team data that may contain a club shortcode.
     * @param object|null $detail Optional detail data used as a fallback source for the club shortcode.
     *
     * @return string The resolved team competitions URL, or an empty string when no valid shortcode is available.
     */
    private function resolve_team_url( object $favourite, ?object $detail ): string {
        $club_shortcode = $favourite->club_shortcode ?? null;
        if ( ( ! is_string( $club_shortcode ) || '' === $club_shortcode ) && is_object( $detail ) ) {
            if ( isset( $detail->club ) && is_object( $detail->club ) && ! empty( $detail->club->shortcode ) && is_string( $detail->club->shortcode ) ) {
                $club_shortcode = $detail->club->shortcode;
            } elseif ( isset( $detail->club_shortcode ) && is_string( $detail->club_shortcode ) ) {
                $club_shortcode = $detail->club_shortcode;
            }
        }

        if ( is_string( $club_shortcode ) && '' !== trim( $club_shortcode ) ) {
            return '/clubs/' . Util::seo_url( trim( $club_shortcode ) ) . '/competitions/';
        }

        return '';
    }

    /**
     * Resolves the tournament URL for a favourite using competition and event detail data.
     *
     * @param object      $favourite Favourite object used as a fallback source for the event name.
     * @param object|null $detail Detail object containing competition and event information.
     *
     * @return string Tournament URL when competition and event names are available, or an empty string otherwise.
     */
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
