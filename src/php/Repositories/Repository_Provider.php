<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

/**
 * Provides access to various repositories in the system.
 */
class Repository_Provider {
    private ?League_Repository $league_repository = null;
    private ?Event_Repository $event_repository = null;
    private ?Competition_Repository $competition_repository = null;
    private ?League_Team_Repository $league_team_repository = null;
    private ?Team_Repository $team_repository = null;
    private ?Player_Repository $player_repository = null;
    private ?Rubber_Repository $rubber_repository = null;
    private ?Results_Checker_Repository $results_checker_repository = null;
    private ?Results_Report_Repository $results_report_repository = null;
    private ?Fixture_Repository $fixture_repository = null;
    private ?Club_Repository $club_repository = null;

    public function __construct(
        ?League_Repository $league_repository = null,
        ?Event_Repository $event_repository = null,
        ?Competition_Repository $competition_repository = null,
        ?League_Team_Repository $league_team_repository = null,
        ?Team_Repository $team_repository = null,
        ?Player_Repository $player_repository = null,
        ?Rubber_Repository $rubber_repository = null,
        ?Results_Checker_Repository $results_checker_repository = null,
        ?Results_Report_Repository $results_report_repository = null,
        ?Fixture_Repository $fixture_repository = null,
        ?Club_Repository $club_repository = null
    ) {
        $this->league_repository = $league_repository;
        $this->event_repository = $event_repository;
        $this->competition_repository = $competition_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository = $team_repository;
        $this->player_repository = $player_repository;
        $this->rubber_repository = $rubber_repository;
        $this->results_checker_repository = $results_checker_repository;
        $this->results_report_repository = $results_report_repository;
        $this->fixture_repository = $fixture_repository;
        $this->club_repository = $club_repository;
    }

   	public function get_event_repository(): Event_Repository {
   		return $this->event_repository ??= new Event_Repository();
   	}

   	public function get_competition_repository(): Competition_Repository {
   		return $this->competition_repository ??= new Competition_Repository();
   	}

   	public function get_league_repository(): League_Repository {
        return $this->league_repository ??= new League_Repository();
    }

    public function get_league_team_repository(): League_Team_Repository {
        return $this->league_team_repository ??= new League_Team_Repository();
    }

    public function get_team_repository(): Team_Repository {
        return $this->team_repository ??= new Team_Repository();
    }

    public function get_player_repository(): Player_Repository {
        return $this->player_repository ??= new Player_Repository();
    }

    public function get_rubber_repository(): Rubber_Repository {
        return $this->rubber_repository ??= new Rubber_Repository();
    }

    public function get_results_checker_repository(): Results_Checker_Repository {
        return $this->results_checker_repository ??= new Results_Checker_Repository();
    }

    public function get_results_report_repository(): Results_Report_Repository {
        return $this->results_report_repository ??= new Results_Report_Repository();
    }

    public function get_fixture_repository(): Fixture_Repository {
        return $this->fixture_repository ??= new Fixture_Repository();
    }

    public function get_club_repository(): Club_Repository {
        return $this->club_repository ??= new Club_Repository();
    }
}
