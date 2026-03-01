<?php
/**
 * Draw page action request DTO
 *
 * NOTE: Controller is responsible for capability + nonce validation.
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Championship
 */

namespace Racketmanager\Domain\DTO\Admin\Championship;

final class Draw_Action_Request_DTO {
    /**
     * @param int $tournament_id
     * @param int $league_id
     * @param string|null $season
     * @param array $post Sanitized/unslashed POST payload (controller-owned)
     */
    public function __construct(
        public readonly int $tournament_id,
        public readonly int $league_id,
        public readonly ?string $season,
        public readonly array $post,
    ) {
    }
}
