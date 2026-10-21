<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering league archive page.
 */
final readonly class League_Archive_View_Model {
    /**
     * @param int|string $league_id
     * @param string $league_title
     * @param string $league_seo_name
     * @param string $competition_type
     * @param string $icon_path
     * @param string $icon_url
     * @param string $calendar_icon_url
     * @param string $event_name
     * @param string $event_url
     * @param string $competition_name
     * @param string $competition_url
     * @param string $current_season_name
     * @param string|null $date_start_display
     * @param string|null $date_end_display
     * @param bool $has_date_range
     * @param int|string $post_id
     * @param string $pagename
     * @param string $form_action
     * @param string $season_title
     * @param string $season_selection
     * @param array<int, mixed> $seasons
     * @param array<int, array<string, mixed>> $season_options
     * @param array<int, array<string, mixed>> $league_options
     * @param int|string $favourite_id
     * @param string $favourite_type
     * @param bool $is_championship
     * @param string|null $championship_season
     * @param array<string, array<string, mixed>> $menu_options
     * @param string $tab
     */
    public function __construct(
        public int|string $league_id,
        public string $league_title,
        public string $league_seo_name,
        public string $competition_type,
        public string $icon_path,
        public string $icon_url,
        public string $calendar_icon_url,
        public string $event_name,
        public string $event_url,
        public string $competition_name,
        public string $competition_url,
        public string $current_season_name,
        public ?string $date_start_display = null,
        public ?string $date_end_display = null,
        public bool $has_date_range = false,
        public int|string $post_id = '',
        public string $pagename = '',
        public string $form_action = '',
        public string $season_title = 'Season',
        public string $season_selection = 'Seasons',
        public array $seasons = [],
        public array $season_options = [],
        public array $league_options = [],
        public int|string $favourite_id = 0,
        public string $favourite_type = 'league',
        public bool $is_championship = false,
        public ?string $championship_season = null,
        public array $menu_options = [],
        public string $tab = 'standings',
        public array $templates = [],
    ) {}

    /**
     * Get specific template filename configured for this view model.
     *
     * @param string $template Template key.
     * @return string
     */
    public function get_template_filename( string $template = '' ): string {
        if ( ! empty( $template ) && isset( $this->templates[ $template ] ) ) {
            return (string) $this->templates[ $template ];
        }

        return '';
    }
}
