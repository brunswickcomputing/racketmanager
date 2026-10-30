<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Racketmanager\Domain\DTO\Tournament\Tournament_Date_DTO;

/**
 * Immutable, pre-formatted tournament dates for display in templates and emails.
 */
final readonly class Tournament_Date_View_Model extends Tournament_Date_DTO {
}
