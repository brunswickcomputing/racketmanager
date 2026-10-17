<?php
declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;

/**
 * Service for managing plugin settings and options.
 */
class Settings_Service {
    private array $options;

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
     * @param string $category e.g., 'rosters', 'league', 'checks'
     * @param mixed $default
     * @return mixed
     */
    public function get_category( string $category, mixed $default = [] ): mixed {
        return $this->options[ $category ] ?? $default;
    }

    /**
     * Get a specific option value within a category.
     *
     * @param string $category
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_option( string $category, string $key, mixed $default = null ): mixed {
        return $this->options[ $category ][ $key ] ?? $default;
    }
}
