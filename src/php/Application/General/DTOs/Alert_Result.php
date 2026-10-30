<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Alert_Handler.
 */
final readonly class Alert_Result {
    public function __construct(
        public string $msg = '',
        public string $type = 'info',
        public ?string $template = null
    ) {}

    public function is_error(): bool {
        return 'danger' === $this->type || 'error' === $this->type;
    }
}
