<?php
/**
 * Admin Message Service
 *
 * @package RacketManager
 * @subpackage Services/Admin
 */

namespace Racketmanager\Services\Admin;

use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;

use function Racketmanager\show_alert;

/**
 * Service to handle admin UI notifications (both flash and current request).
 */
final class Admin_Message_Service {

    /**
     * @var array{message:string, type:string}|null
     */
    private ?array $current_message = null;

    public function __construct(
        private readonly Admin_Flash_Message_Store $flash_store
    ) {
    }

    /**
     * Set a message for the current request.
     *
     * @param string $message
     * @param bool|string|null $error Legacy bridge type: true|'warning'|'info'|'error'|'danger'|false
     */
    public function set_message( string $message, bool|string|null $error = false ): void {
        $this->current_message = [
            'message' => $message,
            'type'    => $this->resolve_type( $error ),
        ];
    }

    /**
     * Set a flash message for the next request.
     *
     * @param string $message
     * @param bool|string|null $error
     */
    public function set_flash_message( string $message, bool|string|null $error = false ): void {
        $this->flash_store->set( $message, $error );
    }

    /**
     * Get and clear the flash message.
     *
     * @return array{message?:string, message_type?:bool|string}
     */
    public function pop_flash_message(): array {
        return $this->flash_store->pop();
    }

    /**
     * Display the current message (if set).
     */
    public function show_message(): void {
        if ( ! $this->current_message ) {
            return;
        }

        echo show_alert( $this->current_message['message'], $this->current_message['type'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $this->current_message = null;
    }

    /**
     * Resolve legacy error type to alert class.
     *
     * @param bool|string|null $error
     * @return string
     */
    private function resolve_type( bool|string|null $error ): string {
        if ( true === $error || 'error' === $error || 'danger' === $error ) {
            return 'danger';
        }
        if ( 'warning' === $error ) {
            return 'warning';
        }
        if ( 'info' === $error ) {
            return 'info';
        }
        return 'success';
    }
}
