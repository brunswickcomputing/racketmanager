<?php
/**
 * Admin action adapter (POST delivery / PRG)
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Admin
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Admin;

use Racketmanager\Services\Admin\Admin_Message_Service;

/**
 * Driving adapter for the mutation (POST) half of an admin screen.
 *
 * Controllers/dispatchers return a result array. When that result carries a `redirect` URL this adapter
 * stores the notice as a flash message and redirects (Post-Redirect-Get). Callers must stop processing
 * when {@see self::deliver_redirect()} returns true so a mutation can never also render a page.
 */
final readonly class Admin_Action_Adapter {

    public function __construct(
        private Admin_Message_Service $message_service,
        private Admin_Redirector_Interface $redirector,
    ) {
    }

    /**
     * @return bool True when the current request is a POST.
     */
    public function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    /**
     * Deliver the PRG redirect for a result, if it has one.
     *
     * @param array{redirect?:string,message?:string,message_type?:bool|string,flash_data?:array<string,mixed>} $result Controller result.
     * @param bool                                                              $post_only Only redirect for POST requests.
     *
     * @return bool True when a redirect was issued (the caller must not render the screen).
     */
    public function deliver_redirect( array $result, bool $post_only = false ): bool {
        if ( empty( $result['redirect'] ) ) {
            return false;
        }
        if ( $post_only && ! $this->is_post_request() ) {
            return false;
        }

        if ( ! empty( $result['message'] ) ) {
            if ( ! empty( $result['flash_data'] ) ) {
                $this->message_service->set_flash_message(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false,
                    $result['flash_data']
                );
            } else {
                $this->message_service->set_flash_message(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }
        }

        $this->redirector->redirect( strval( $result['redirect'] ) );

        return true;
    }
}
