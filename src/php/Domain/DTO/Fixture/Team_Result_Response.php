<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for team result update and confirmation response.
 */
class Team_Result_Response {
    /**
     * @var string
     */
    public string $msg = '';

    /**
     * @var array
     */
    public array $rubbers = [];

    /**
     * @var string|int
     */
    public string|int $status = 'success';

    /**
     * @var int|null
     */
    public ?int $winner_id = null;

    /**
     * @var int|null
     */
    public ?int $loser_id = null;

    /**
     * @var array
     */
    public array $warnings = [];

    /**
     * @var bool
     */
    public bool $error = false;

    /**
     * @var array
     */
    public array $err_msgs = [];

    /**
     * @var array
     */
    public array $err_flds = [];

    /**
     * Team_Result_Response constructor.
     *
     * @param array $data
     */
    public function __construct( array $data = [] ) {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }
}
