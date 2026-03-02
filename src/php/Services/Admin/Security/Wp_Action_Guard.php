<?php
/**
 * WordPress admin action guard (nonce + capability)
 *
 * @package RacketManager
 * @subpackage Services/Admin/Security
 */

namespace Racketmanager\Services\Admin\Security;

use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Validator\Validator;

final class Wp_Action_Guard {

    /**
     * @throws Invalid_Status_Exception
     */
    public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void {
        $v = new Validator();
        $v = $v->check_security_token( $nonce_field, $nonce_action );
        $v = $v->capability( $capability );

        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }
    }
}
