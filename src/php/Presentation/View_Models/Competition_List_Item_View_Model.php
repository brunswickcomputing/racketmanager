<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering items in a competition list.
 */
final readonly class Competition_List_Item_View_Model {
    public function __construct(
        public string $name,
        public string $link,
        public ?string $icon_path = null,
        public ?string $venue_name = null,
        public ?string $date_start_display = null,
        public ?string $date_end_display = null,
        public bool $has_date_range = false,
        public bool $is_open = false,
        public ?string $entry_url = null
    ) {}
}
