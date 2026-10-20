<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable presentation row for a tournament player's event entry.
 */
final readonly class Tournament_Player_Entry_Row_View_Model {
    public function __construct(
        public int|string $event_id,
        public string $event_name,
        public string $event_link,
        public mixed $partner_id = null,
        public string $partner_name = '',
        public string $partner_link = '',
    ) {}
}
