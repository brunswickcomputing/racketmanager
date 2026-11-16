<?php
declare( strict_types=1 );
/**
 * Upgrade page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Class Upgrade_Page_View_Model
 *
 * Holds state for the "database upgrade required" administrative screen.
 */
final readonly class Upgrade_Page_View_Model {

    /**
     * @param string|null $installed      Currently installed database version.
     * @param string|null $latest_version Target/latest database version.
     */
    public function __construct(
        public ?string $installed = null,
        public ?string $latest_version = null,
    ) {
    }

    /**
     * Convert to template variables array.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'installed'      => $this->installed,
            'latest_version' => $this->latest_version,
        );
    }
}
