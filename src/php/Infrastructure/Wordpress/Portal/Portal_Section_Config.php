<?php
declare( strict_types=1 );
/**
 * Portal Section Config
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * Value object representing configuration and migration status for a management section.
 */
final readonly class Portal_Section_Config {

    /**
     * @param string $slug          URL slug for frontend management (e.g. 'clubs').
     * @param string $admin_page    Legacy wp-admin page slug (e.g. 'racketmanager-clubs').
     * @param string $screen_class  Handling screen class or view model.
     * @param string $capability    Required WordPress user capability.
     * @param string $default_view  Default subview when not specified.
     * @param bool   $is_ported     Whether the section is ported to the frontend portal shell.
     * @param string $title         Human-readable title for navigation.
     * @param bool   $in_navigation Whether to display this section in the primary portal navigation bar.
     * @param string $group         Optional functional cluster / group label for navigation.
     */
    public function __construct(
        public string $slug,
        public string $admin_page,
        public string $screen_class,
        public string $capability,
        public string $default_view,
        public bool $is_ported = false,
        public string $title = '',
        public bool $in_navigation = true,
        public string $group = '',
    ) {
    }

    /**
     * Creates a copy of this configuration with the frontend portal ported flag updated.
     *
     * @param bool $is_ported Whether the section is ported to the frontend portal shell.
     *
     * @return self New instance with the updated ported state.
     */
    public function with_ported( bool $is_ported ): self {
        return new self(
            $this->slug,
            $this->admin_page,
            $this->screen_class,
            $this->capability,
            $this->default_view,
            $is_ported,
            $this->title,
            $this->in_navigation,
            $this->group
        );
    }
}
