<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for fixture / match views.
 */
final readonly class Fixture_View_Model {
    /**
     * @param string      $template          Template name.
     * @param object|null $fixture           Fixture entity or adapter.
     * @param array       $rubbers           Rubber entities / view models.
     * @param array       $teams             Home & away team data.
     * @param array|null  $location          Venue & secretary contact information.
     * @param array       $approvals         Approval status per team.
     * @param string|null $comments          General match comments.
     * @param bool        $is_editing        Whether match is in editing/score submission mode.
     * @param bool        $is_standalone     Whether match is displayed on standalone page.
     * @param bool        $user_can_update   Whether current user has permission to update result.
     * @param string|null $error_message     Error message if fixture could not be loaded.
     */
    public function __construct(
        public string $template = 'match',
        public ?object $fixture = null,
        public array $rubbers = [],
        public array $teams = [],
        public ?array $location = null,
        public array $approvals = [],
        public ?string $comments = null,
        public bool $is_editing = false,
        public bool $is_standalone = false,
        public bool $user_can_update = false,
        public ?string $error_message = null
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'fixture'         => $this->fixture,
            'match'           => $this->fixture,
            'rubbers'         => $this->rubbers,
            'teams'           => $this->teams,
            'location'        => $this->location,
            'approvals'       => $this->approvals,
            'comments'        => $this->comments,
            'is_editing'      => $this->is_editing,
            'is_standalone'   => $this->is_standalone,
            'user_can_update' => $this->user_can_update,
        ];
    }
}
