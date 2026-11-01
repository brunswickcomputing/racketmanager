<?php
declare( strict_types=1 );
/**
 * Cup Draw Page View Model
 *
 * View model for cup draw admin screen (view=draw).
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Cup_Draw_Page_View_Model {

    /**
     * @param object $competition
     * @param object $league
     * @param string $season
     * @param string $tab
     * @param array  $finals
     * @param string|null $page
     * @param string|null $view
     */
    public function __construct(
        public object $competition,
        public object $league,
        public string $season,
        public string $tab,
        public array $finals,
        public ?string $page = null,
        public ?string $view = null,
    ) {
    }

    /**
     * Convert to template variables array for BC extraction.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition' => $this->competition,
            'league'      => $this->league,
            'season'      => $this->season,
            'tab'         => $this->tab,
            'finals'      => $this->finals,
            'page'        => $this->page,
            'view'        => $this->view,
        );
    }
}
