<?php
/**
 * Draw action handler port
 *
 * Lets Draw_Action_Dispatcher be unit-tested without WordPress by injecting a stub.
 *
 * @package RacketManager
 * @subpackage Services/Admin/Championship
 */

namespace Racketmanager\Services\Admin\Championship;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;

interface Draw_Action_Handler_Interface {
    public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
    public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
    public function manage_matches_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
    public function rank_teams( Draw_Action_Request_DTO $dto, string $mode ): Action_Result_DTO;
    public function start_finals( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
    public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
    public function set_championship_matches( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
}
