<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for fixture status updates.
 */
final class Fixture_Status_Read_Model {
    public int $match_id;
    public ?string $match_status;
    public array $status_message;
    public array $status_class;
    public ?string $modal;
    public int $num_rubbers;
    public ?int $rubber_number;

    public function __construct(
        int $match_id,
        ?string $match_status,
        array $status_message,
        array $status_class,
        ?string $modal,
        int $num_rubbers,
        ?int $rubber_number = null
    ) {
        $this->match_id       = $match_id;
        $this->match_status   = $match_status;
        $this->status_message = $status_message;
        $this->status_class   = $status_class;
        $this->modal          = $modal;
        $this->num_rubbers    = $num_rubbers;
        $this->rubber_number  = $rubber_number;
    }

    public function to_array(): array {
        $array = array(
            'match_id'       => $this->match_id,
            'match_status'   => $this->match_status,
            'status_message' => $this->status_message,
            'status_class'   => $this->status_class,
            'modal'          => $this->modal,
            'num_rubbers'    => $this->num_rubbers,
        );

        if ( null !== $this->rubber_number ) {
            $array['rubber_number'] = $this->rubber_number;
        }

        return $array;
    }
}
