<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

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
     */
    public function __construct(
        public string $template = 'standings',
        public ?object $league = null,
        public array $teams = [],
        public bool $widget = false,
        public ?string $season = null,
        public ?string $group = null
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'league' => $this->league,
            'teams'  => $this->teams,
            'widget' => $this->widget,
            'season' => $this->season,
        ];
    }
}
