<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable presentation row for a tournament events list item.
 */
final readonly class Tournament_Event_List_Row_View_Model {
    public function __construct(
        public int|string $id,
        public string $name,
        public mixed $num_teams,
        public string $url_link,
    ) {}
}
