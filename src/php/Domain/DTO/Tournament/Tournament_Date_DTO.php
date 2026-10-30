<?php
declare( strict_types=1 );
/**
 * Tournament Date DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Tournament
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Immutable, pre-formatted tournament dates for display in templates, services, and emails.
 */
readonly class Tournament_Date_DTO {
    public function __construct(
        public string $date_display = 'TBC',
        public string $date_closing_display = 'N/A',
        public string $date_withdrawal_display = 'N/A',
        public string $date_open_display = 'N/A',
        public string $date_start_display = 'N/A',
        public string $date_end_display = 'TBC'
    ) {}
}
