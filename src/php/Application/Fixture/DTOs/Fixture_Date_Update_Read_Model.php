<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for fixture date updates.
 */
final class Fixture_Date_Update_Read_Model {
    public function __construct(
        public string $msg,
        public int $match_id,
        public string $schedule_date,
        public string $schedule_date_formated,
        public ?string $modal = null
    ) {
    }

    public function to_array(): array {
        return [
            'msg'                    => $this->msg,
            'match_id'               => $this->match_id,
            'schedule_date'          => $this->schedule_date,
            'schedule_date_formated' => $this->schedule_date_formated,
            'modal'                  => $this->modal,
        ];
    }
}
