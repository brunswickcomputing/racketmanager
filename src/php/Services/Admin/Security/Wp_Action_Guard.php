<?php
/**
 * WordPress admin action guard (nonce and capability)
 *
 * @package RacketManager
 * @subpackage Services/Admin/Security
 */

namespace Racketmanager\Services\Admin\Security;

use Closure;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Validator\Validator;

final class Wp_Action_Guard implements Action_Guard_Interface {

    /**
     * @var Closure():object
     */
    private Closure $validator_factory;

    /**
     * @param callable():object|null $validator_factory Factory returning a validator-like object.
     *                                                 Default uses the real Validator.
     */
    public function __construct( ?callable $validator_factory = null ) {
        $factory = $validator_factory ?? static fn (): object => new Validator();
        $this->validator_factory = $factory( ... );
    }

    /**
     * @throws Invalid_Status_Exception
     */
    public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void {
        $v = ( $this->validator_factory )();

        if ( ! method_exists( $v, 'check_security_token' ) || ! method_exists( $v, 'capability' ) ) {
            throw new Invalid_Status_Exception( 'Invalid validator factory' );
        }

        $v = $v->check_security_token( $nonce_field, $nonce_action );
        $v = $v->capability( $capability );

        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }
    }

    /**
     * @throws Invalid_Status_Exception
     */
    public function assert_capability( string $capability ): void {
        $v = ( $this->validator_factory )();

        if ( ! method_exists( $v, 'capability' ) ) {
            throw new Invalid_Status_Exception( 'Invalid validator factory' );
        }

        $v = $v->capability( $capability );

        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }
    }
}
