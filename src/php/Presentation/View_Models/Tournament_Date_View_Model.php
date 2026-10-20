<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable, pre-formatted tournament dates for display in templates and emails.
 */
final readonly class Tournament_Date_View_Model {
    public function __construct(
        public string $date_display = 'TBC',
        public string $date_closing_display = 'N/A',
        public string $date_withdrawal_display = 'N/A',
        public string $date_open_display = 'N/A',
        public string $date_start_display = 'N/A',
        public string $date_end_display = 'TBC'
    ) {}
}
