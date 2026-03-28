<?php

namespace Racketmanager\Exceptions;

class Fixture_Validation_Exception extends Plugin_Exception {
    private array $error_flds;
    private array $error_msgs;

    public function __construct(array $error_msgs, array $error_flds = array(), int $code = 400) {
        $this->error_msgs = $error_msgs;
        $this->error_flds = $error_flds;
        parent::__construct(reset($error_msgs), $code);
    }

    public function get_error_flds(): array {
        return $this->error_flds;
    }

    public function get_error_msgs(): array {
        return $this->error_msgs;
    }
}
