<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Strict immutable view model for club profile, header, and directory views.
 */
final readonly class Club_View_Model {
    /**
     * @param string      $template                   Template name or suffix ('club', 'club-custom').
     * @param int         $id                         Club unique ID.
     * @param string      $name                       Club display name.
     * @param string      $shortcode                  Club shortcode / abbreviation.
     * @param string      $slug                       Club URL slug.
     * @param string|null $address                    Club street / postal address.
     * @param string      $link                       Canonical club URL link.
     * @param array       $subpage_links              Links to club subpages (roles, competitions, players).
     * @param string|null $map_url                    Google Maps embed URL if address and API key are present.
     * @param string      $initials                   Club initials derived from shortcode.
     * @param string      $heading_tag                Heading HTML tag ('h1', 'h2', 'h3', etc.).
     * @param array       $roles                      Club role assignments.
     * @param array       $teams                      Club teams.
     * @param string      $google_maps_key            Google Maps API key.
     * @param bool        $user_can_update_club       Whether current user has match secretary / admin permissions.
     * @param bool        $standalone                 Whether displayed on standalone club page.
     * @param string|null $match_secretary_name       Match secretary display name.
     * @param int|string|null $match_secretary_id     Match secretary user/player ID.
     * @param string|null $match_secretary_email      Match secretary email.
     * @param string|null $match_secretary_contact_no Match secretary contact telephone.
     * @param string|null $contact_no                 Club telephone / contact number.
     * @param string|null $website                    Club website URL.
     * @param string|null $founded                    Year club was founded.
     * @param string|null $facilities                 Club court facilities summary.
     * @param string|null $error_message              Error message if club could not be loaded.
     * @param object|null $club                       Domain entity or object for backward-compatible template access.
     */
    public function __construct(
        public string $template = 'club',
        public int $id = 0,
        public string $name = '',
        public string $shortcode = '',
        public string $slug = '',
        public ?string $address = null,
        public string $link = '',
        public array $subpage_links = [],
        public ?string $map_url = null,
        public string $initials = '',
        public string $heading_tag = 'h1',
        public array $roles = [],
        public array $teams = [],
        public string $google_maps_key = '',
        public bool $user_can_update_club = false,
        public bool $standalone = true,
        public ?string $match_secretary_name = null,
        public int|string|null $match_secretary_id = null,
        public ?string $match_secretary_email = null,
        public ?string $match_secretary_contact_no = null,
        public ?string $contact_no = null,
        public ?string $website = null,
        public ?string $founded = null,
        public ?string $facilities = null,
        public ?string $error_message = null,
        public ?object $club = null
    ) {}

    /**
     * Factory for error view model.
     */
    public static function for_error( string $error_message, string $template = 'alert' ): self {
        return new self(
            template: $template,
            error_message: $error_message
        );
    }

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'vm'                         => $this,
            'club'                       => $this->club,
            'google_maps_key'            => $this->google_maps_key,
            'user_can_update_club'       => $this->user_can_update_club,
            'standalone'                 => $this->standalone,
            'initials'                   => $this->initials,
            'heading_tag'                => $this->heading_tag,
            'heading_type'               => $this->heading_tag,
            'map_url'                    => $this->map_url,
            'club_link'                  => $this->link,
            'club_name'                  => $this->name,
            'subpage_links'              => $this->subpage_links,
            'roles'                      => $this->roles,
            'teams'                      => $this->teams,
            'match_secretary_name'       => $this->match_secretary_name,
            'match_secretary_id'         => $this->match_secretary_id,
            'match_secretary_email'      => $this->match_secretary_email,
            'match_secretary_contact_no' => $this->match_secretary_contact_no,
            'contact_no'                 => $this->contact_no,
            'website'                    => $this->website,
            'address'                    => $this->address,
            'founded'                    => $this->founded,
            'facilities'                 => $this->facilities,
        ];
    }
}
