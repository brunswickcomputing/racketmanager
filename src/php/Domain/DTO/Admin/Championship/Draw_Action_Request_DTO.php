<?php
/**
 * Draw page action request DTO
 *
 * NOTE: Controller is responsible for capability and nonce validation.
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Championship
 */

namespace Racketmanager\Domain\DTO\Admin\Championship;

readonly class Draw_Action_Request_DTO {
    /**
     * @param int $tournament_id
     * @param int $league_id
     * @param string|null $season
     * @param array $post Sanitised/unslashed POST payload (controller-owned)
     */
    public function __construct(
        public int $tournament_id,
        public int $league_id,
        public ?string $season,
        public array $post,
    ) {
    }
}
