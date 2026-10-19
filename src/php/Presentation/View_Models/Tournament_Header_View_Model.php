<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering tournament headers.
 */
final readonly class Tournament_Header_View_Model {
    public function __construct(
        public string $name,
        public ?string $venue_name = null,
        public ?string $date_start_display = null,
        public ?string $date_end_display = null,
        public bool $has_date_range = false,
        public bool $show_entry_button = false,
        public ?string $entry_url = null
    ) {}
}
