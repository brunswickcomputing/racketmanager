<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for switching home and away teams.
 */
final class Fixture_Switch_Teams_Read_Model {
    public function __construct(
        public string $msg,
        public int $match_id,
        public string $link,
        public ?string $modal = null
    ) {
    }

    public function to_array(): array {
        return [
            'msg'      => $this->msg,
            'match_id' => $this->match_id,
            'link'     => $this->link,
            'modal'    => $this->modal,
        ];
    }
}
