<?php
declare( strict_types=1 );
/**
 * Tournament_Update_DTO API: Tournament_Update_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Pure, already-sanitised tournament attributes applied by Tournament::update_from_request().
 *
 * Built by the HTTP adapter (Infrastructure\Wordpress\Request\Tournament\Tournament_Request_DTO::to_update_dto())
 * so that the Tournament aggregate never depends on request parsing.
 */
readonly class Tournament_Update_DTO {
    public function __construct(
        public ?string $name = null,
        public ?int $competition_id = null,
        public ?string $season = null,
        public ?int $venue = null,
        public ?string $date_open = null,
        public ?string $date_closing = null,
        public ?string $date_withdrawal = null,
        public ?string $date_start = null,
        public ?string $date_end = null,
        public ?string $competition_code = null,
        public ?string $grade = null,
        public ?int $num_entries = null
    ) {
    }
}
