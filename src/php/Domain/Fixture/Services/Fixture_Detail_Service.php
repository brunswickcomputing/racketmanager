<?php

namespace Racketmanager\Domain\Fixture\Services;

use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;

class Fixture_Detail_Service {
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private Competition_Repository_Interface $competition_repository;
    private Team_Repository_Interface $team_repository;
    private Club_Repository_Interface $club_repository;
    private Rubber_Repository_Interface $rubber_repository;
    private League_Team_Repository_Interface $league_team_repository;
    private Player_Repository_Interface $player_repository;
    private Fixture_Link_Service $link_service;
    private Fixture_Permission_Service $permission_service;
    private Club_Role_Repository_Interface $club_role_repository;

    public function __construct(
        League_Repository_Interface $league_repository,
        Event_Repository_Interface $event_repository,
        Competition_Repository_Interface $competition_repository,
        Team_Repository_Interface $team_repository,
        Club_Repository_Interface $club_repository,
        Rubber_Repository_Interface $rubber_repository,
        League_Team_Repository_Interface $league_team_repository,
        Player_Repository_Interface $player_repository,
        Fixture_Link_Service $link_service,
        Fixture_Permission_Service $permission_service,
        Club_Role_Repository_Interface $club_role_repository
    ) {
        $this->league_repository = $league_repository;
        $this->event_repository = $event_repository;
        $this->competition_repository = $competition_repository;
        $this->team_repository = $team_repository;
        $this->club_repository = $club_repository;
        $this->rubber_repository = $rubber_repository;
        $this->league_team_repository = $league_team_repository;
        $this->player_repository = $player_repository;
        $this->link_service = $link_service;
        $this->permission_service = $permission_service;
        $this->club_role_repository = $club_role_repository;
    }

    /**
     * Enriches the DTO with additional business logic data required for presentation.
     *
     * @param Fixture_Details_DTO $dto
     * @return Fixture_Details_DTO
     */
    public function enrich( Fixture_Details_DTO $dto ): Fixture_Details_DTO {
        $fixture = $dto->fixture;

        // Populate league, event, competition
        if ( $fixture->get_league_id() ) {
            $dto->league = $this->league_repository->find_by_id( $fixture->get_league_id() );
            if ( $dto->league ) {
                $dto->event = $this->event_repository->find_by_id( $dto->league->get_event_id() );
                if ( $dto->event ) {
                    $dto->competition = $this->competition_repository->find_by_id( $dto->event->get_competition_id() );
                }
            }
        }

        // Load team details
        $dto->home_team = $this->get_team_details( (int) $fixture->get_home_team(), (int) $fixture->get_league_id(), $fixture->get_season() );
        $dto->away_team = $this->get_team_details( (int) $fixture->get_away_team(), (int) $fixture->get_league_id(), $fixture->get_season() );

        // Set Title
        $home_name = $dto->home_team ? $dto->home_team->team->get_name() : __( 'Unknown', 'racketmanager' );
        $away_name = $dto->away_team ? $dto->away_team->team->get_name() : __( 'Unknown', 'racketmanager' );
        $dto->fixture_title = sprintf( '%s vs %s', $home_name, $away_name );

        // Set Link
        if ( $dto->league ) {
            $dto->fixture_link = $this->link_service->get_fixture_link(
                $fixture,
                $dto->league,
                $dto->home_team,
                $dto->away_team
            );
            $dto->link         = $dto->fixture_link;
        }

        // Load Rubbers
        $dto->rubbers = $this->rubber_repository->find_by_fixture_id( $fixture->get_id() );

        // Resolve Approver names
        $home_approver_id = $fixture->get_home_approver();
        if ( $home_approver_id ) {
            $player                  = $this->player_repository->find( $home_approver_id );
            $dto->home_approver_name = $player?->get_fullname();
        }

        $away_approver_id = $fixture->get_away_approver();
        if ( $away_approver_id ) {
            $player                  = $this->player_repository->find( $away_approver_id );
            $dto->away_approver_name = $player?->get_fullname();
        }

        // Add permissions
        $dto->is_update_allowed = $this->permission_service->is_update_allowed( $fixture );

        // Load Captain details
        $this->enrich_captain_details( $dto );

        // Status flags
        $dto->enriched_data['is_knockout'] = ! empty( $fixture->get_final() );

        // Club players for result entry
        $this->enrich_club_players( $dto );

        return $dto;
    }

    /**
     * Enriches the DTO with club players for result entry.
     */
    private function enrich_club_players( Fixture_Details_DTO $dto ): void {
        if ( ! $dto->event ) {
            return;
        }

        $fixture = $dto->fixture;
        $opponents = [ 'home', 'away' ];

        foreach ( $opponents as $opponent ) {
            $dto->club_players[ $opponent ] = [ 'm' => [], 'f' => [] ];
            $team_id = 'home' === $opponent ? (int) $fixture->get_home_team() : (int) $fixture->get_away_team();
            if ( $team_id === 0 ) {
                continue;
            }

            $team = $this->team_repository->find_by_id( $team_id );
            if ( ! $team || ! $team->get_club_id() ) {
                continue;
            }

            $club_id = (int) $team->get_club_id();
            
            // Re-introduce gender-based filtering based on event type
            $event = $dto->event;
            $type = $event instanceof Event ? $event->get_type() : ( $event->type ?? '' );

            $map_players = function ( $p_obj ) {
                $player = $this->player_repository->find( $p_obj->user_id );
                $player?->set_id( $p_obj->registration_id );
                return $player;
            };

            switch ( $type ) {
                case 'BD':
                case 'MD':
                case 'MS':
                case 'BS':
                    $players_m = $this->player_repository->find_club_players_with_details( $club_id, null, 'm', 'active' );
                    $dto->club_players[ $opponent ]['m'] = array_filter( array_map( $map_players, $players_m ) );
                    break;
                case 'GD':
                case 'WD':
                case 'WS':
                case 'GS':
                    $players_f = $this->player_repository->find_club_players_with_details( $club_id, null, 'f', 'active' );
                    $dto->club_players[ $opponent ]['f'] = array_filter( array_map( $map_players, $players_f ) );
                    break;
                case 'XD':
                case 'LD':
                    $players_m = $this->player_repository->find_club_players_with_details( $club_id, null, 'm', 'active' );
                    $players_f = $this->player_repository->find_club_players_with_details( $club_id, null, 'f', 'active' );
                    $dto->club_players[ $opponent ]['m'] = array_filter( array_map( $map_players, $players_m ) );
                    $dto->club_players[ $opponent ]['f'] = array_filter( array_map( $map_players, $players_f ) );
                    break;
                default:
                    // Fallback to both if type is unknown
                    $players_m = $this->player_repository->find_club_players_with_details( $club_id, null, 'm', 'active' );
                    $players_f = $this->player_repository->find_club_players_with_details( $club_id, null, 'f', 'active' );
                    $dto->club_players[ $opponent ]['m'] = array_filter( array_map( $map_players, $players_m ) );
                    $dto->club_players[ $opponent ]['f'] = array_filter( array_map( $map_players, $players_f ) );
                    break;
            }
        }

        // Ensure players already assigned to rubbers are included in club_players list
        if ( ! empty( $dto->rubbers ) ) {
            foreach ( $dto->rubbers as $rubber ) {
                $rubber_players = $rubber->get_players(); // Ensure players are loaded
                foreach ( $opponents as $opponent ) {
                    $assigned_players = $rubber->players[ $opponent ] ?? [];
                    foreach ( $assigned_players as $player_dto ) {
                        if ( ! $player_dto ) {
                            continue;
                        }
                        $gender = strtolower( $player_dto->gender ?? 'm' );
                        if ( ! isset( $dto->club_players[ $opponent ][ $gender ] ) ) {
                            $dto->club_players[ $opponent ][ $gender ] = [];
                        }

                        $reg_id = $player_dto->registration_id;
                        $found  = false;
                        foreach ( $dto->club_players[ $opponent ][ $gender ] as $existing_player ) {
                            if ( $existing_player->get_id() === $reg_id ) {
                                $found = true;
                                break;
                            }
                        }

                        if ( ! $found ) {
                            $player = $this->player_repository->find( $player_dto->user_id );
                            if ( $player ) {
                                $player->set_id( $reg_id );
                                $dto->club_players[ $opponent ][ $gender ][] = $player;
                            }
                        }
                    }
                }
            }
            
            // Re-sort the lists if we added players
            foreach ( $opponents as $opponent ) {
                foreach ( [ 'm', 'f' ] as $gender ) {
                    if ( ! empty( $dto->club_players[ $opponent ][ $gender ] ) ) {
                        usort( $dto->club_players[ $opponent ][ $gender ], function ( $a, $b ) {
                            return strcmp( $a->get_fullname(), $b->get_fullname() );
                        } );
                    }
                }
            }
        }
    }

    /**
     * Enriches the DTO with captain details for both home and away teams.
     */
    private function enrich_captain_details( Fixture_Details_DTO $dto ): void {
        $fixture = $dto->fixture;
        $league_id = (int) $fixture->get_league_id();
        $season = $fixture->get_season();

        $opponents = [ 'home', 'away' ];
        foreach ( $opponents as $opponent ) {
            $team_id = 'home' === $opponent ? (int) $fixture->get_home_team() : (int) $fixture->get_away_team();
            if ( $team_id === 0 ) {
                continue;
            }

            $league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season );
            if ( $league_team ) {
                $captain_id = $league_team->get_captain();
                if ( $captain_id ) {
                    $player = $this->player_repository->find( $captain_id );
                    if ( 'home' === $opponent ) {
                        $dto->home_captain_name = $player?->get_fullname();
                        $dto->home_captain_contactno = $player?->get_contactno() ?? null;
                        $dto->home_captain_email = $player?->get_email() ?? null;
                    } else {
                        $dto->away_captain_name = $player?->get_fullname();
                        $dto->away_captain_contactno = $player?->get_contactno() ?? null;
                        $dto->away_captain_email = $player?->get_email() ?? null;
                    }
                }

            }
        }
    }

    private function get_team_details( int $team_id, int $league_id, string $season ): ?Team_Details_DTO {
        if ( $team_id === 0 ) {
            return null;
        }

        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            return null;
        }

        $club = $this->club_repository->find_by_id( $team->get_club_id() );
        $league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season );

        $match_secretary = null;
        if ( $club ) {
            $roles = $this->club_role_repository->search( array( 'club' => $club->get_id(), 'role' => 1 ) );
            foreach ( $roles as $role ) {
                if ( $role->get_user_id() ) {
                    $match_secretary = $this->player_repository->find( $role->get_user_id() );
                    break;
                }
            }
        }

        return new Team_Details_DTO(
            $team,
            $club,
            $match_secretary,
            $league_team && $league_team->get_status() === 'withdrawn'
        );
    }
}
