<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Racketmanager\Application\League\DTOs\League_View_State;

/**
 * Immutable view model for rendering league archive page.
 */
final class League_Archive_View_Model {

    public readonly int|string $league_id;
    public readonly string $league_name;

    /**
     * Backward-compatible property hook for legacy callers reading $vm->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    public readonly string $league_seo_name;
    public readonly string $competition_type;
    public readonly string $icon_path;
    public readonly string $icon_url;
    public readonly string $calendar_icon_url;
    public readonly string $event_name;
    public readonly string $event_url;
    public readonly string $competition_name;
    public readonly string $competition_url;
    public readonly string $current_season_name;
    public readonly ?string $date_start_display;
    public readonly ?string $date_end_display;
    public readonly bool $has_date_range;
    public readonly int|string $post_id;
    public readonly string $pagename;
    public readonly string $form_action;
    public readonly string $season_title;
    public readonly string $season_selection;
    public readonly array $seasons;
    public readonly array $season_options;
    public readonly array $league_options;
    public readonly int|string $favourite_id;
    public readonly string $favourite_type;
    public readonly bool $is_championship;
    public readonly ?string $championship_season;
    public readonly array $menu_options;
    public readonly string $tab;
    public readonly array $templates;
    public readonly ?League_View_State $view_state;

    public function __construct(
        int|string $league_id,
        string $league_name,
        string $league_seo_name,
        string $competition_type,
        string $icon_path,
        string $icon_url,
        string $calendar_icon_url,
        string $event_name,
        string $event_url,
        string $competition_name,
        string $competition_url,
        string $current_season_name,
        ?string $date_start_display = null,
        ?string $date_end_display = null,
        bool $has_date_range = false,
        int|string $post_id = '',
        string $pagename = '',
        string $form_action = '',
        string $season_title = 'Season',
        string $season_selection = 'Seasons',
        array $seasons = [],
        array $season_options = [],
        array $league_options = [],
        int|string $favourite_id = 0,
        string $favourite_type = 'league',
        bool $is_championship = false,
        ?string $championship_season = null,
        array $menu_options = [],
        string $tab = 'standings',
        array $templates = [],
        ?League_View_State $view_state = null
    ) {
        $this->league_id           = $league_id;
        $this->league_name         = $league_name;
        $this->league_seo_name     = $league_seo_name;
        $this->competition_type    = $competition_type;
        $this->icon_path           = $icon_path;
        $this->icon_url            = $icon_url;
        $this->calendar_icon_url   = $calendar_icon_url;
        $this->event_name          = $event_name;
        $this->event_url           = $event_url;
        $this->competition_name    = $competition_name;
        $this->competition_url     = $competition_url;
        $this->current_season_name = $current_season_name;
        $this->date_start_display  = $date_start_display;
        $this->date_end_display    = $date_end_display;
        $this->has_date_range      = $has_date_range;
        $this->post_id             = $post_id;
        $this->pagename            = $pagename;
        $this->form_action         = $form_action;
        $this->season_title        = $season_title;
        $this->season_selection    = $season_selection;
        $this->seasons             = $seasons;
        $this->season_options      = $season_options;
        $this->league_options      = $league_options;
        $this->favourite_id        = $favourite_id;
        $this->favourite_type      = $favourite_type;
        $this->is_championship     = $is_championship;
        $this->championship_season = $championship_season;
        $this->menu_options        = $menu_options;
        $this->tab                 = $tab;
        $this->templates           = $templates;
        $this->view_state          = $view_state;
    }

    /**
     * Get specific template filename configured for this view model.
     *
     * @param string $template Template key.
     * @return string
     */
    public function get_template_filename( string $template = '' ): string {
        if ( ! empty( $template ) && $this->view_state?->has_template( $template ) ) {
            return $this->view_state->get_template( $template );
        }

        if ( ! empty( $template ) && isset( $this->templates[ $template ] ) ) {
            return (string) $this->templates[ $template ];
        }

        return '';
    }
}
