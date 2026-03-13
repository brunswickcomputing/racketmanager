<?php
/**
 * Tournament overview action request DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Overview
 */

namespace Racketmanager\Domain\DTO\Admin\Overview;

readonly class Tournament_Overview_Action_Request_DTO {
    /**
     * @param int|null $tournament_id
     * @param array $post Sanitised/unslashed POST payload (controller-owned)
     */
    public function __construct(
        public ?int $tournament_id,
        public array $post,
    ) {
    }
}
