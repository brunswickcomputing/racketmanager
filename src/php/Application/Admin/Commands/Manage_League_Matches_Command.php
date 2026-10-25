<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Manage_League_Matches_Command {
    public function __construct(
        public string $mode,
        public array $match,
        public ?string $season,
        public ?string $final,
        public ?string $league_id,
        public ?int $num_rubbers,
        public array|int|null $match_day,
        public array $home_team,
        public array $away_team,
        public array $host,
        public array $location,
        public array $myDatePicker,
        public array $begin_hour,
        public array $begin_minutes,
        public array $year,
        public array $month,
        public array $day
    ) {
        Command_Validation::column( $this->match, 'match' );
        if ( 'add' !== $this->mode ) {
            Command_Validation::ids( $this->match, 'match' );
        }
        Command_Validation::column( $this->match_day, 'match_day' );
        Command_Validation::column( $this->home_team, 'home_team' );
        Command_Validation::column( $this->away_team, 'away_team' );
        Command_Validation::column( $this->host, 'host' );
        Command_Validation::column( $this->location, 'location' );
        Command_Validation::column( $this->myDatePicker, 'myDatePicker' );
        Command_Validation::column( $this->begin_hour, 'begin_hour' );
        Command_Validation::column( $this->begin_minutes, 'begin_minutes' );
        Command_Validation::column( $this->year, 'year' );
        Command_Validation::column( $this->month, 'month' );
        Command_Validation::column( $this->day, 'day' );
    }
}
