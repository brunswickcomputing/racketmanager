<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Racketmanager\Presentation\Presenters\League_Standings_Presenter;

/**
 * View model for the [standings] shortcode.
 */
final readonly class League_Standings_View_Model {
    /**
     * @param string      $template   Template name to render.
     * @param object|null $league     League domain entity.
     * @param array       $teams      Ordered league teams list.
     * @param bool        $widget     Widget display flag.
     * @param string|null $season     Active season.
     * @param string|null $group      Active group.
     * @param array       $team_links Precalculated team URLs.
     */
    public function __construct(
        public string $template = 'standings',
        public ?object $league = null,
        public array $teams = [],
        public bool $widget = false,
        public ?string $season = null,
        public ?string $group = null,
        public array $team_links = []
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'league'     => $this->league,
            'teams'      => $this->teams,
            'widget'     => $this->widget,
            'season'     => $this->season,
            'team_links' => $this->team_links,
        ];
    }

    /**
     * Check if a specific standings column is activated for display in this view model.
     *
     * @param string $key Column key.
     * @return bool
     */
    public function show_column( string $key ): bool {
        return League_Standings_Presenter::is_column_visible( $this->league, $key );
    }

    /**
     * Get specific template filename configured for this view model.
     *
     * @param string $template Template key.
     * @return string
     */
    public function get_template_filename( string $template = '' ): string {
        return League_Standings_Presenter::get_league_template( $this->league, $template );
    }
}
