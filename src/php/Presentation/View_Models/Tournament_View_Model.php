<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for [tournament] shortcode and frontend tournament views.
 */
final readonly class Tournament_View_Model {
    /**
     * @param string      $template           Template name or suffix.
     * @param object|null $tournament         Tournament domain entity or object.
     * @param array       $events             Tournament events.
     * @param array       $draws              Tournament draws.
     * @param array       $entries            Tournament entries.
     * @param string|null $tab                Active tab.
     * @param string|null $error_message      Error message if tournament could not be loaded.
     */
    public function __construct(
        public string $template = 'tournament',
        public ?object $tournament = null,
        public array $events = [],
        public array $draws = [],
        public array $entries = [],
        public ?string $tab = null,
        public ?string $error_message = null
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'tournament' => $this->tournament,
            'events'     => $this->events,
            'draws'      => $this->draws,
            'entries'    => $this->entries,
            'tab'        => $this->tab,
        ];
    }
}
