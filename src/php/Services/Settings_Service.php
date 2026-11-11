<?php
declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;

/**
 * Service for managing plugin settings and options.
 */
class Settings_Service implements Settings_Service_Interface {
    /**
     * @var array
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param array|null                    $options options.
     * @param Option_Gateway_Interface|null $option_gateway option gateway.
     */
    public function __construct( ?array $options = null, ?Option_Gateway_Interface $option_gateway = null ) {
        if ( null !== $options ) {
            $this->options = $options;
        } elseif ( null !== $option_gateway ) {
            $this->options = (array) $option_gateway->get_option( 'racketmanager', [] );
        } else {
            $this->options = [];
        }
    }

    /**
     * Get all plugin options.
     *
     * @return array
     */
    public function get_all_options(): array {
        return $this->options;
    }

    /**
     * Get a specific option category.
     *
     * @param string $category e.g., 'rosters', 'league', 'checks'.
     * @param mixed  $default_option default.
     *
     * @return mixed
     */
    public function get_category( string $category, mixed $default_option = [] ): mixed {
        return $this->options[ $category ] ?? $default_option;
    }

    /**
     * Get a specific option value within a category.
     *
     * @param string $category category.
     * @param string $key key.
     * @param mixed  $default default.
     * @return mixed
     */
    public function get_option( string $category, string $key, mixed $default = null ): mixed {
        return $this->options[ $category ][ $key ] ?? $default;
    }

    /**
     * Get a specific field value within a category (alias of get_option).
     *
     * @param string $category      Category name.
     * @param string $field         Field name.
     * @param mixed  $default_value Default value if field does not exist.
     * @return mixed
     */
    public function get_field( string $category, string $field, mixed $default_value = null ): mixed {
        return $this->get_option( $category, $field, $default_value );
    }
}
