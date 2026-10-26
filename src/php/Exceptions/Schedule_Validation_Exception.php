<?php
declare( strict_types=1 );

namespace Racketmanager\Exceptions;

/**
 * Raised when a competition schedule cannot be generated or persisted safely
 * (e.g. a division has more teams than the schedule can hold, or a generated
 * match has no playable date).
 */
class Schedule_Validation_Exception extends Plugin_Exception {
    private array $error_msgs;

    public function __construct( array $error_msgs, int $code = 400 ) {
        $this->error_msgs = array_values( $error_msgs );
        parent::__construct( (string) ( reset( $error_msgs ) ?: 'Schedule validation failed' ), $code );
    }

    public function get_error_msgs(): array {
        return $this->error_msgs;
    }
}
