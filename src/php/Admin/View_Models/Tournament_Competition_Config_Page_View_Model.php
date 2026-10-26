<?php
declare( strict_types=1 );
/**
 * Tournament competition config page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Tournament\Tournament;

readonly final class Tournament_Competition_Config_Page_View_Model {

    public object $config;
    public string $title;

    /**
     * @param Competition $competition
     * @param Tournament|null $tournament
     * @param array $rules_options
     * @param Club[] $clubs
     * @param Event[] $events
     * @param string $tab
     */
    public function __construct(
        public Competition $competition,
        public ?Tournament $tournament,
        public array $rules_options,
        public array $clubs,
        public array $events,
        public string $tab = 'general',
        public Error_Bag $errors = new Error_Bag(),
        ?object $config = null,
        ?string $title = null,
    ) {
        $this->config = $config ?? ( $competition->config ?? (object) $competition->get_settings() );
        $this->title  = $title ?? ( $competition->name ?? '' );
    }

    /**
     * Export variables expected by templates/admin/includes/competition-config.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition'   => $this->competition,
            'tournament'    => $this->tournament,
            'rules_options' => $this->rules_options,
            'clubs'         => $this->clubs,
            'events'        => $this->events,
            'tab'           => $this->tab,
            'errors'        => $this->errors,
            'config'        => $this->config,
            'title'         => $this->title,
            // Legacy variables that were previously set in display_config_page
            'forwin'           => 0,
            'fordraw'          => 0,
            'forloss'          => 0,
            'forwin_overtime'  => 0,
            'forloss_overtime' => 0,
            'is_invalid'       => false,
        );
    }
}
