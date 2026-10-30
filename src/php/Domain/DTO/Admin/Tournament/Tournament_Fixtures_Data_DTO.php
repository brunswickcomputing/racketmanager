<?php
declare( strict_types=1 );
/**
 * Tournament Fixtures Data DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Tournament
 */

namespace Racketmanager\Domain\DTO\Admin\Tournament;

final readonly class Tournament_Fixtures_Data_DTO {

    /**
     * @param array<int,object> $fixtures
     * @param array<int,object> $teams
     */
    public function __construct(
        public object $league,
        public object $tournament,
        public object $competition,
        public string $season,
        public string $form_title,
        public string $submit_title,
        public array $fixtures,
        public bool $edit,
        public bool $bulk,
        public bool $is_finals,
        public string $mode,
        public array $teams,
        public bool $single_cup_game,
        public int $max_fixtures,
        public string $final_key,
        public string $home_title = '',
        public string $away_title = '',
        public mixed $match_day = null,
    ) {
    }
}
