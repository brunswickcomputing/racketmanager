<?php
declare( strict_types=1 );
/**
 * Flash Message Store Interface
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

interface Flash_Message_Store_Interface {
    /**
     * Set a flash message.
     *
     * @param string              $message      Message text.
     * @param bool|string         $message_type Message type or level.
     * @param array<string,mixed> $data         Additional flash data.
     */
    public function set( string $message, bool|string $message_type = false, array $data = array() ): void;

    /**
     * Pop the stored flash message.
     *
     * @return array{message?:string, message_type?:bool|string, ts?:int, flash_data?:array<string,mixed>}
     */
    public function pop(): array;
}
