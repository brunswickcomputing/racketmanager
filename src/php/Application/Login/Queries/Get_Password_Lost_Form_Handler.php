<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Application\Login\DTOs\Password_Lost_Form_Result;
use Racketmanager\Util\Util;
use function __;

class Get_Password_Lost_Form_Handler {
    public function handle( Get_Password_Lost_Form_Query $query ): Password_Lost_Form_Result {
        if ( $query->is_logged_in ) {
            return Password_Lost_Form_Result::html(
                __( 'You are already signed in', 'racketmanager' )
            );
        }

        $errors      = [];
        $error_codes = [];
        if ( ! empty( $query->errors ) ) {
            $error_codes = explode( ',', $query->errors );
            foreach ( $error_codes as $code ) {
                $errors[] = Util::get_error_message( trim( $code ) );
            }
        }

        $vars = [
            'show_title'         => $query->show_title,
            'errors'             => $errors,
            'error_codes'        => $error_codes,
            'lost_password_sent' => 'confirm' === $query->checkemail,
        ];

        return Password_Lost_Form_Result::template( 'form-password-lost', $vars, 'form' );
    }
}
