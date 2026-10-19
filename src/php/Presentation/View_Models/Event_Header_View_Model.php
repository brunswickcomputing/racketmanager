<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering event headers.
 */
final readonly class Event_Header_View_Model {
    /**
     * @param string $name
     * @param int $header_level
     * @param string|null $icon_path
     * @param string|null $competition_name
     * @param string|null $competition_link
     * @param string|null $date_start_display
     * @param string|null $date_end_display
     * @param bool $has_date_range
     * @param bool $show_season_selector
     * @param string|int $post_id
     * @param string $pagename
     * @param string $form_action
     * @param string $season_label
     * @param array<int, mixed> $seasons
     * @param string $curr_season
     */
    public function __construct(
        public string $name,
        public int $header_level = 1,
        public ?string $icon_path = null,
        public ?string $competition_name = null,
        public ?string $competition_link = null,
        public ?string $date_start_display = null,
        public ?string $date_end_display = null,
        public bool $has_date_range = false,
        public bool $show_season_selector = true,
        public string|int $post_id = '',
        public string $pagename = '',
        public string $form_action = '',
        public string $season_label = 'Season',
        public array $seasons = [],
        public string $curr_season = ''
    ) {}
}
