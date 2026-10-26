<?php
declare( strict_types=1 );
/**
 * Admin flash message store (user meta)
 *
 * @package RacketManager
 * @subpackage Admin/Flash
 */

namespace Racketmanager\Admin\Flash;

class Admin_Flash_Message_Store {
    private const string META_KEY = 'racketmanager_admin_flash_message';

    /**
     * @param string $message
     * @param bool|string         $message_type Legacy bridge type: true|'warning'|'info'|false
     * @param array<string,mixed> $data       Additional view state to preserve across a PRG redirect.
     */
    public function set( string $message, bool|string $message_type = false, array $data = array() ): void {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return;
        }

        $payload = array(
            'message'      => $message,
            'message_type' => $message_type,
            'ts'           => time(),
        );
        if ( ! empty( $data ) ) {
            $payload['flash_data'] = $data;
        }

        update_user_meta( $user_id, self::META_KEY, $payload );
    }

    /**
     * Pop the flash message (read once).
     *
     * @return array{message?:string,message_type?:bool|string,ts?:int,flash_data?:array<string,mixed>}
     */
    public function pop(): array {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return array();
        }

        $payload = get_user_meta( $user_id, self::META_KEY, true );
        if ( empty( $payload ) || ! is_array( $payload ) ) {
            return array();
        }

        delete_user_meta( $user_id, self::META_KEY );

        return $payload;
    }
}
