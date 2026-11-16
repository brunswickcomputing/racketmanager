<?php
declare( strict_types=1 );
/**
 * Draw action handler port
 *
 * Lets Draw_Action_Dispatcher be unit-tested without WordPress by injecting a stub.
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;

interface Draw_Action_Handler_Interface {
    /**
     * Handle league teams action.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO;

    /**
     * Add teams to league.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO;

    /**
     * Manage fixtures in league.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function manage_fixtures_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO;

    /**
     * Rank teams.
     *
     * @param Draw_Action_Request_DTO $dto  Request DTO.
     * @param string                  $mode Ranking mode.
     * @return Action_Result_DTO
     */
    public function rank_teams( Draw_Action_Request_DTO $dto, string $mode ): Action_Result_DTO;

    /**
     * Start finals.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function start_finals( Draw_Action_Request_DTO $dto ): Action_Result_DTO;

    /**
     * Update final results.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO;

    /**
     * Set championship fixtures.
     *
     * @param Draw_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function set_championship_fixtures( Draw_Action_Request_DTO $dto ): Action_Result_DTO;
}
