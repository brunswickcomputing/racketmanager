<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Favourites_Result;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use function __;
use function is_object;

class Get_Favourites_Handler {
    public function __construct(
        private readonly User_Repository_Interface $user_repository,
        private readonly Club_Repository_Interface $club_repository
    ) {}

    public function handle( Get_Favourites_Query $query ): Favourites_Result {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Favourites_Result::error( __( 'You must be logged in to view favourites', 'racketmanager' ) );
        }

        $user = $this->user_repository->find( $query->user_id );

        $favourites = $user
            ? ( $this->user_repository->get_favourites( $user ) ?: [] )
            : [];

        $prepared_favourites = [];
        foreach ( $favourites as $group ) {
            $group_name  = $group['name'] ?? '';
            $group_items = [];
            foreach ( $group['favourites'] ?? [] as $fav_item ) {
                if ( ! is_object( $fav_item ) ) {
                    $group_items[] = $fav_item;
                    continue;
                }
                $item_copy = (object) [
                    'id'     => $fav_item->id ?? null,
                    'name'   => $fav_item->name ?? null,
                    'detail' => $fav_item->detail ?? null,
                ];
                if ( 'team' === $group_name && is_object( $fav_item->detail ?? null ) ) {
                    $club_id = $fav_item->detail->club_id ?? null;
                    if ( $club_id ) {
                        $club = $this->club_repository->find_by_id( (int) $club_id );
                        if ( $club && ! empty( $club->shortcode ) ) {
                            $item_copy->club_shortcode = (string) $club->shortcode;
                        }
                    }
                }
                $group_items[] = $item_copy;
            }
            $prepared_favourites[] = [
                'name'       => $group_name,
                'favourites' => $group_items,
            ];
        }

        return new Favourites_Result(
            favourite_types: $prepared_favourites,
            template: $query->template
        );
    }
}
