<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering competition headers.
 */
final readonly class Competition_Header_View_Model {
    /**
     * @param string $name
     * @param string|null $season_title
     * @param string|null $icon_path
     * @param string|null $venue_name
     * @param string|null $date_start_display
     * @param string|null $date_end_display
     * @param bool $has_date_range
     * @param string|null $entry_link
     * @param bool $show_season_selector
     * @param string|int $post_id
     * @param string $pagename
     * @param string $form_action
     * @param array<int, array<string, mixed>> $seasons
     * @param string $current_season_name
     */
    public function __construct(
        public string $name,
        public ?string $season_title = null,
        public ?string $icon_path = null,
        public ?string $venue_name = null,
        public ?string $date_start_display = null,
        public ?string $date_end_display = null,
        public bool $has_date_range = false,
        public ?string $entry_link = null,
        public bool $show_season_selector = false,
        public string|int $post_id = '',
        public string $pagename = '',
        public string $form_action = '',
        public array $seasons = [],
        public string $current_season_name = ''
    ) {}
}
