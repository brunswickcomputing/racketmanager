<?php
declare( strict_types=1 );
/**
 * Flash Message Store Interface
 *
 * @package RacketManager
 * @subpackage Services/Admin
 */

namespace Racketmanager\Services\Admin;

interface Flash_Message_Store_Interface {
    /**
     * @param string $message
     * @param bool|string $message_type
     * @param array<string,mixed> $data
     */
    public function set( string $message, bool|string $message_type = false, array $data = array() ): void;

    /**
     * @return array{message?:string, message_type?:bool|string, ts?:int, flash_data?:array<string,mixed>}
     */
    public function pop(): array;
}
