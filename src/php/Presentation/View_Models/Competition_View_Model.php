<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for the [competition] shortcode.
 */
final readonly class Competition_View_Model {
    /**
     * @param string      $template           Template suffix or name ('competition', 'competition-custom').
     * @param object|null $competition        The competition domain entity.
     * @param array       $competition_season Current season configuration array.
     * @param string|null $tab                Active tab.
     * @param array       $seasons            Available seasons.
     * @param string|null $error_message      Error message if competition could not be loaded.
     */
    public function __construct(
        public string $template = 'competition',
        public ?object $competition = null,
        public array $competition_season = [],
        public ?string $tab = null,
        public array $seasons = [],
        public ?string $error_message = null
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'competition'        => $this->competition,
            'competition_season' => $this->competition_season,
            'tab'                => $this->tab,
            'seasons'            => $this->seasons,
        ];
    }
}
