<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for the fixture detail fragment.
 */
class Fixture_Detail_Read_Model {
    public function __construct(
        public array $rubbers,
        public array $approvals,
        public ?string $general_comments,
        public bool $is_standalone = false,
        public ?array $focused_match_info = null,
        public ?array $location = null,
        public ?array $teams = null,
        public bool $show_print_button = false,
        public bool $show_edit_button = false,
        public int $match_id = 0,
        public string $edit_url = "",
        public bool $is_editing = false,
        public array $club_players = [],
        public ?string $match_status = null,
        public array $permissions = [],
        public int $num_sets = 3,
        public array $scoring_info = []
    ) {}
}
