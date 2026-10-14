<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for the [club] shortcode.
 */
final readonly class Club_View_Model {
    /**
     * @param string      $template             Template suffix or name ('club', 'club-custom').
     * @param object|null $club                 The club details entity/object.
     * @param string      $google_maps_key      Google Maps API key.
     * @param bool        $user_can_update_club Permission flag if the current user can manage the club.
     * @param bool        $standalone           Whether rendered standalone.
     * @param string|null $error_message        Error message if club could not be loaded.
     */
    public function __construct(
        public string $template = 'club',
        public ?object $club = null,
        public string $google_maps_key = '',
        public bool $user_can_update_club = false,
        public bool $standalone = true,
        public ?string $error_message = null
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'club'                 => $this->club,
            'google_maps_key'      => $this->google_maps_key,
            'user_can_update_club' => $this->user_can_update_club,
            'standalone'           => $this->standalone,
        ];
    }
}
