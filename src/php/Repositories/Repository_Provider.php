<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use RuntimeException;

/**
 * Provides access to various repositories in the system.
 */
class Repository_Provider {
    public function __construct(
        private ?League_Repository_Interface $league_repository = null,
        private ?Event_Repository_Interface $event_repository = null,
        private ?Competition_Repository_Interface $competition_repository = null,
        private ?League_Team_Repository_Interface $league_team_repository = null,
        private ?Team_Repository_Interface $team_repository = null,
        private ?Player_Repository_Interface $player_repository = null,
        private ?Rubber_Repository_Interface $rubber_repository = null,
        private ?Results_Checker_Repository_Interface $results_checker_repository = null,
        private ?Results_Report_Repository_Interface $results_report_repository = null,
        private ?Result_Repository_Interface $result_repository = null,
        private ?Fixture_Repository_Interface $fixture_repository = null,
        private ?Club_Repository_Interface $club_repository = null
    ) {
    }

    public function get_event_repository(): Event_Repository_Interface {
        if ( null === $this->event_repository ) {
            throw new RuntimeException( 'Repository_Provider: Event_Repository_Interface is not set.' );
        }
        return $this->event_repository;
    }

    public function get_competition_repository(): Competition_Repository_Interface {
        if ( null === $this->competition_repository ) {
            throw new RuntimeException( 'Repository_Provider: Competition_Repository_Interface is not set.' );
        }
        return $this->competition_repository;
    }

    public function get_league_repository(): League_Repository_Interface {
        if ( null === $this->league_repository ) {
            throw new RuntimeException( 'Repository_Provider: League_Repository_Interface is not set.' );
        }
        return $this->league_repository;
    }

    public function get_league_team_repository(): League_Team_Repository_Interface {
        if ( null === $this->league_team_repository ) {
            throw new RuntimeException( 'Repository_Provider: League_Team_Repository_Interface is not set.' );
        }
        return $this->league_team_repository;
    }

    public function get_team_repository(): Team_Repository_Interface {
        if ( null === $this->team_repository ) {
            throw new RuntimeException( 'Repository_Provider: Team_Repository_Interface is not set.' );
        }
        return $this->team_repository;
    }

    public function get_player_repository(): Player_Repository_Interface {
        if ( null === $this->player_repository ) {
            throw new RuntimeException( 'Repository_Provider: Player_Repository_Interface is not set.' );
        }
        return $this->player_repository;
    }

    public function get_rubber_repository(): Rubber_Repository_Interface {
        if ( null === $this->rubber_repository ) {
            throw new RuntimeException( 'Repository_Provider: Rubber_Repository_Interface is not set.' );
        }
        return $this->rubber_repository;
    }

    public function get_results_checker_repository(): Results_Checker_Repository_Interface {
        if ( null === $this->results_checker_repository ) {
            throw new RuntimeException( 'Repository_Provider: Results_Checker_Repository_Interface is not set.' );
        }
        return $this->results_checker_repository;
    }

    public function get_results_report_repository(): Results_Report_Repository_Interface {
        if ( null === $this->results_report_repository ) {
            throw new RuntimeException( 'Repository_Provider: Results_Report_Repository_Interface is not set.' );
        }
        return $this->results_report_repository;
    }

    public function get_result_repository(): Result_Repository_Interface {
        if ( null === $this->result_repository ) {
            throw new RuntimeException( 'Repository_Provider: Result_Repository_Interface is not set.' );
        }
        return $this->result_repository;
    }

    public function get_fixture_repository(): Fixture_Repository_Interface {
        if ( null === $this->fixture_repository ) {
            throw new RuntimeException( 'Repository_Provider: Fixture_Repository_Interface is not set.' );
        }
        return $this->fixture_repository;
    }

    public function get_club_repository(): Club_Repository_Interface {
        if ( null === $this->club_repository ) {
            throw new RuntimeException( 'Repository_Provider: Club_Repository_Interface is not set.' );
        }
        return $this->club_repository;
    }
}
