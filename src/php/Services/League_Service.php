<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\League;
use Racketmanager\Domain\League_Team;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use stdClass;

class League_Service {

    private RacketManager $racketmanager;
    private League_Team_Repository $league_team_repository;
    private League_Repository $league_repository;
    private Event_Repository $event_repository;
    private Team_Repository $team_repository;

    public function __construct( RacketManager $plugin_instance, League_Repository $league_repository, Event_Repository $event_repository, League_Team_Repository $league_team_repository, Team_Repository $team_repository ) {
        $this->racketmanager          = $plugin_instance;
        $this->league_repository      = $league_repository;
        $this->event_repository       = $event_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository        = $team_repository;
    }

    public function add_league_to_event( int $event_id, ?string $name ): League {
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
        }
        $league = new stdClass();
        if ( empty( $name ) ) {
            // 1. Get the next available sequence number
            $next_sequence_number = $this->league_repository->find_next_sequence_number( $event->get_name() );
            // 2. Generate the team name
            $league_name = $event->get_name() . ' ' . $next_sequence_number;
            // 3. Create the Domain Entity and Save it
            $league->title = $league_name;
        } else {
            $league->title = $name;
        }
        $league->event_id = $event->get_id();
        $league           = new League( $league );
        $this->league_repository->save( $league );

        return $league;
    }

    public function add_team_to_league( int $team_id, int $league_id, int $season ): League_Team {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( __( 'Team not found', 'racketmanager' ) );
        }
        $league = $this->league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }
        $lt            = new stdClass();
        $lt->league_id = $league_id;
        $lt->team_id   = $team_id;
        $lt->season    = $season;
        $league_team   = new League_Team( $lt );
        $this->league_team_repository->save( $league_team );
        return $league_team;
    }

}
