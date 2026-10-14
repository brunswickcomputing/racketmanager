<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Util\Util;
use function __;

class Get_Password_Reset_Form_Handler {
    public function handle( Get_Password_Reset_Form_Query $query ): Shortcode_View_Model {
        if ( empty( $query->login ) || empty( $query->key ) ) {
            return Shortcode_View_Model::for_html(
                __( 'Invalid password reset link.', 'racketmanager' )
            );
        }

        $errors      = [];
        $error_codes = [];
        if ( ! empty( $query->error ) ) {
            $error_codes = explode( ',', $query->error );
            foreach ( $error_codes as $code ) {
                $errors[] = Util::get_error_message( trim( $code ) );
            }
        }

        $vars = [
            'show_title'  => $query->show_title,
            'login'       => $query->login,
            'key'         => $query->key,
            'errors'      => $errors,
            'error_codes' => $error_codes,
        ];

        return Shortcode_View_Model::for_template( 'form-password-reset', $vars, 'form' );
    }
}
