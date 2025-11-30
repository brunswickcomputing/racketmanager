<?php
/**
 * Service Contracts: WTN API client interface
 *
 * Defines the contract for classes that communicate with external services
 * to retrieve or update World Tennis Number (WTN) or other player-related data.
 *
 * Architectural placement:
 * - Interface lives under Services/Contracts
 * - Concrete implementations live under Services/External
 * - Domain/services that need external data should depend on this interface
 *   and receive an implementation via dependency injection.
 *
 * This keeps HTTP concerns isolated and makes it easy to mock for tests.
 */

namespace Racketmanager\Services\Contracts;

interface Wtn_Api_Client_Interface {
    /**
     * Prepare HTTP/environment arguments (cookies, headers, etc.) required to call the external API.
     *
     * @return void
     */
    public function prepare_env(): void;

    /**
     * Fetch the WTN for a specific player using the previously prepared context/args.
     * The return value should be a simple DTO-like array with keys: status (bool), value (mixed), message (string).
     *
     * @param object $player A player object which must at least expose ->btm (LTA number).
     * @return array{status:bool,value:mixed,message:string}
     */
    public function fetch_player_wtn( object $player ): array;
}
