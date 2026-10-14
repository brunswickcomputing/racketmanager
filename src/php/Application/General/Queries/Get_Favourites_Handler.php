<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use function __;
use function function_exists;
use function is_object;
use function method_exists;
use function Racketmanager\get_user;

class Get_Favourites_Handler {
    public function handle( Get_Favourites_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Shortcode_View_Model::for_error( __( 'You must be logged in to view favourites', 'racketmanager' ) );
        }

        $user = function_exists( 'Racketmanager\get_user' )
            ? get_user( $query->user_id )
            : ( function_exists( 'get_user' ) ? \get_user( $query->user_id ) : null );

        $favourites = ( is_object( $user ) && method_exists( $user, 'get_favourites' ) )
            ? ( $user->get_favourites() ?: [] )
            : [];

        $filename = ! empty( $query->template ) ? 'form-favourites-' . $query->template : 'form-favourites';

        return Shortcode_View_Model::for_template( $filename, [ 'favourite_types' => $favourites ], 'form' );
    }
}
