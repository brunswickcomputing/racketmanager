<?php
/**
 * Simple error bag for templates/view-models
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Error_Bag {
    /**
     * @param array<string,string> $messages_by_field
     */
    public function __construct(
        private array $messages_by_field = array(),
    ) {
    }

    public function has( string $field ): bool {
        return array_key_exists( $field, $this->messages_by_field ) && '' !== $this->messages_by_field[ $field ];
    }

    public function message( string $field ): ?string {
        if ( ! $this->has( $field ) ) {
            return null;
        }
        return $this->messages_by_field[ $field ];
    }

    /**
     * @return array<string,string>
     */
    public function all(): array {
        return $this->messages_by_field;
    }
}
