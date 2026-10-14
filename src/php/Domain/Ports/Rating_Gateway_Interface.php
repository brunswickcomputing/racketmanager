<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Ports;

/**
 * Domain Port for external player rating services (e.g. LTA / World Tennis Number WTN).
 */
interface Rating_Gateway_Interface {
    /**
     * Prepare HTTP/environment context (cookies, headers, session) required to call the external rating service.
     *
     * @return void
     */
    public function prepare_env(): void;

    /**
     * Fetch the rating/WTN details for a specific player entity or object.
     *
     * @param object $player A player object exposing at least ->btm (LTA number).
     * @return array{status:bool,value?:mixed,message?:string}
     */
    public function fetch_player_wtn( object $player ): array;
}
