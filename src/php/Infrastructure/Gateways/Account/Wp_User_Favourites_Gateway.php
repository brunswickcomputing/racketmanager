<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Gateways\Account;

use Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;

final readonly class Wp_User_Favourites_Gateway implements User_Favourites_Gateway_Interface {
    public function __construct( private Security_Service_Interface $security ) {}

    public function get_current_user_favourites(): ?array {
        if ( ! $this->security->is_user_logged_in() ) {
            return null;
        }

        $user_id = $this->security->get_current_user_id();
        if ( $user_id <= 0 ) {
            return null;
        }

        $favourites = [];
        foreach ( [ 'competition', 'league', 'club', 'team', 'player' ] as $type ) {
            $favourites[ $type ] = get_user_meta( $user_id, 'favourite-' . $type );
        }
        return $favourites;
    }
}