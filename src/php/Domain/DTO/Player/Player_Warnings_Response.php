<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for player validation warnings in a fixture.
 */
class Player_Warnings_Response {
    /**
     * @var string
     */
    public string $msg = '';

    /**
     * @var string|null
     */
    public ?string $status = null;

    /**
     * @var array
     */
    public array $warnings = [];

    /**
     * Player_Warnings_Response constructor.
     *
     * @param string $msg
     * @param string|null $status
     * @param array $warnings
     */
    public function __construct( string $msg = '', ?string $status = null, array $warnings = [] ) {
        $this->msg      = $msg;
        $this->status   = $status;
        $this->warnings = $warnings;
    }
}
