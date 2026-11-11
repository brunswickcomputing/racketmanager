<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Ports;

/**
 * Port interface for settings management and configuration access.
 */
interface Settings_Service_Interface {
    /**
     * Get a specific option category.
     *
     * @param string $category      Category name (e.g., 'rosters', 'league', 'checks').
     * @param mixed  $default_value Default value if category does not exist.
     * @return mixed
     */
    public function get_category( string $category, mixed $default_value = [] ): mixed;

    /**
     * Get a specific field value within a category.
     *
     * @param string $category      Category name.
     * @param string $field         Field name.
     * @param mixed  $default_value Default value if field does not exist.
     * @return mixed
     */
    public function get_field( string $category, string $field, mixed $default_value = null ): mixed;
}
