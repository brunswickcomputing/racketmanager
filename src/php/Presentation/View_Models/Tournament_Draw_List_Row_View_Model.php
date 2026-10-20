<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable presentation row for a tournament draws list item.
 */
final readonly class Tournament_Draw_List_Row_View_Model {
    public function __construct(
        public int|string $id,
        public string $name,
        public mixed $draw_size,
        public string $draw_link,
    ) {}
}
