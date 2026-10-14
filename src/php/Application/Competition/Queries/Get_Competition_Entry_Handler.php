<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use BackedEnum;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function array_column;
use function in_array;
use function intval;
use function is_object;
use function method_exists;
use function str_starts_with;
use function substr;
use function __;

final readonly class Get_Competition_Entry_Handler {
    public function __construct(
        private Club_Entry_Selection_Service $club_entry_selection,
        private Competition_Service $competition_service,
        private Tournament_Service $tournament_service,
        private Player_Service $player_service,
        private Club_Service $club_service,
        private Team_Service $team_service,
        private Registration_Service $registration_service,
        private Competition_Entry_Service $competition_entry_service,
        private Finance_Service $finance_service
    ) {}

    public function handle( Get_Competition_Entry_Query $query ): Shortcode_View_Model {
        $is_tournament    = false;
        $tournament_name  = $query->tournament_name;
        $competition_name = $query->competition_name;
        $tournament       = null;
        $player           = null;

        if ( $tournament_name ) {
            $is_tournament = true;
        } elseif ( ! $competition_name ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( $is_tournament ) {
            try {
                $tournament = $this->tournament_service->get_tournament_by_name( (string) $tournament_name );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return Shortcode_View_Model::error( $e->getMessage() );
            }
        }

        $competition_ref = ( $is_tournament && $tournament ) ? $tournament->get_competition_id() : $competition_name;

        try {
            $competition = $this->competition_service->get_by_id( $competition_ref );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( $competition->is_tournament ) {
            if ( $query->player_id ) {
                try {
                    $player = $this->player_service->get_player_by_name( $query->player_id );
                } catch ( Player_Not_Found_Exception $e ) {
                    return Shortcode_View_Model::error( $e->getMessage() );
                }
            } else {
                try {
                    $player = $this->player_service->get_player( $query->user_id );
                } catch ( Player_Not_Found_Exception $e ) {
                    return Shortcode_View_Model::error( $e->getMessage() );
                }
            }

            if ( ! $tournament ) {
                return Shortcode_View_Model::error( __( 'Tournament not found', 'racketmanager' ) );
            }

            return $this->build_tournament_entry( $tournament, $player, $query->template );
        }

        // League / Cup flow
        if ( ! $query->season ) {
            return Shortcode_View_Model::error( __( 'No season specified', 'racketmanager' ) );
        }

        $competition_season = $competition->get_season_by_name( $query->season );
        if ( ! $competition_season ) {
            return Shortcode_View_Model::error( __( 'Season not found', 'racketmanager' ) );
        }

        if ( ! empty( $competition_season['venue'] ) ) {
            try {
                $venue_club = $this->club_service->get_club( (int) $competition_season['venue'] );
                $competition_season['venue_name'] = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception ) {
                // venue club not found
            }
        }

        $club = null;
        if ( $query->club_name ) {
            try {
                $club = $this->club_service->get_club_by_shortcode( $query->club_name );
            } catch ( Club_Not_Found_Exception ) {
                $club = null;
            }
            if ( ! $club ) {
                return Shortcode_View_Model::error( __( 'Club not found', 'racketmanager' ) );
            }

            $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );
            $can_enter = $this->club_entry_selection->available_clubs(
                $query->is_admin,
                $query->user_id,
                (int) $club_id,
                $query->entry_level
            );

            if ( ! $can_enter ) {
                return Shortcode_View_Model::error( __( 'User not authorised for club entry for this competition', 'racketmanager' ) );
            }
        } else {
            $clubs = $this->club_entry_selection->available_clubs(
                $query->is_admin,
                $query->user_id,
                null,
                $query->entry_level
            );

            if ( $clubs ) {
                return new Shortcode_View_Model(
                    'entry-form-clubs-list',
                    [
                        'competition'        => $competition,
                        'season'             => $query->season,
                        'competition_season' => $competition_season,
                        'clubs'              => $clubs,
                    ]
                );
            }

            return Shortcode_View_Model::error( __( 'No club specified', 'racketmanager' ) );
        }

        $comp_type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? '' );
        if ( $comp_type instanceof BackedEnum ) {
            $comp_type = $comp_type->value;
        }

        return match ( (string) $comp_type ) {
            'league' => $this->build_league_entry( $competition, $query->season, (array) $competition_season, $club, $query->template ),
            'cup'    => $this->build_cup_entry( $competition, $query->season, (array) $competition_season, $club, $query->template ),
            default  => Shortcode_View_Model::error( __( 'Invalid competition type specified', 'racketmanager' ) ),
        };
    }

    private function build_cup_entry( object $competition, string $season, array $competition_season, object $club, string $template ): Shortcode_View_Model {
        $events  = method_exists( $competition, 'get_events' ) ? $competition->get_events() : ( $competition->events ?? [] );
        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );

        foreach ( $events as $i => $event ) {
            if ( ! ( $event instanceof Event ) ) {
                $event_id = is_object( $event ) && isset( $event->id ) ? (int) $event->id : (int) $event;
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Competition_Not_Found_Exception ) {
                    $event = null;
                }
            }
            if ( ! ( $event instanceof Event ) ) {
                unset( $events[ $i ] );
                continue;
            }
            $event->status = '';
            $event_teams   = $event->get_teams(
                [
                    'season' => $season,
                    'club'   => $club_id,
                ]
            );
            foreach ( $event_teams as $event_team ) {
                $event_team->team_info     = $event->get_team_info( $event_team->team_id );
                $event->team               = $event_team;
                $event->status             = 'checked';
                $club->entry[ $event->id ] = $event;
            }
        }

        $ladies_teams = $this->team_service->get_teams_for_club( (int) $club_id, 'WD' );
        $mens_teams   = $this->team_service->get_teams_for_club( (int) $club_id, 'MD' );
        $mixed_teams  = $this->team_service->get_teams_for_club( (int) $club_id, 'XD' );
        $match_days   = Util_Lookup::get_match_days();

        $filename = ( ! empty( $template ) ) ? 'entry-cup-' . $template : 'entry-cup';

        return new Shortcode_View_Model(
            $filename,
            [
                'club'               => $club,
                'events'             => $events,
                'ladies_teams'       => $ladies_teams,
                'mens_teams'         => $mens_teams,
                'mixed_teams'        => $mixed_teams,
                'season'             => $season,
                'competition'        => $competition,
                'competition_season' => $competition_season,
                'match_days'         => $match_days,
            ],
            'entry'
        );
    }

    private function build_league_entry( object $competition, string $season, array $competition_season, object $club, string $template ): Shortcode_View_Model {
        $events  = method_exists( $competition, 'get_events' ) ? $competition->get_events() : ( $competition->events ?? [] );
        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );

        foreach ( $events as $i => $event ) {
            if ( ! ( $event instanceof Event ) ) {
                $event_id = is_object( $event ) && isset( $event->id ) ? (int) $event->id : (int) $event;
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Competition_Not_Found_Exception ) {
                    $event = null;
                }
            }
            if ( ! ( $event instanceof Event ) ) {
                unset( $events[ $i ] );
                continue;
            }
            $event->status = '';
            $event_teams   = $event->get_teams(
                [
                    'season' => $season,
                    'club'   => $club_id,
                ]
            );
            foreach ( $event_teams as $c => $event_team ) {
                $event_team->team_info = $event->get_team_info( $event_team->team_id );
                if ( '0' === $event_team->profile || '1' === $event_team->profile || '2' === $event_team->profile ) {
                    $event_team->status = 'checked';
                    $event->status      = 'checked';
                } else {
                    $event_team->status = '';
                }
                $event_teams[ $c ] = $event_team;
            }
            $event->event_teams = $event_teams;
            $event_type         = $event->type;
            if ( 'LD' === $event->type ) {
                $event_type = 'XD';
            }
            $event->teams = $this->team_service->get_teams_for_club( (int) $club_id, $event_type );
            $key = 0;
            foreach ( $event->teams as $team ) {
                $found = in_array( $team->id, array_column( $event->event_teams, 'team_id' ) );
                if ( false !== $found ) {
                    unset( $event->teams[ $key ] );
                }
                ++$key;
            }
            $key = 0;
            foreach ( $event->teams as $team ) {
                if ( ! empty( $team->league_id ) ) {
                    $event_team            = new stdClass();
                    $event_team->team_id   = $team->id;
                    $event_team->name      = $team->title ?? $team->name ?? '';
                    $event_team->league_id = 0;
                    $event_team->status    = null;
                    $event->event_teams[]  = $event_team;
                }
                ++$key;
            }
            $event_team            = new stdClass();
            $event_team->team_id   = 0;
            $event_team->name      = __( 'New team', 'racketmanager' );
            $event_team->league_id = 0;
            $event_team->status    = null;
            $event->event_teams[]  = $event_team;
            $events[ $i ]          = $event;
            if ( ! empty( $event->status ) ) {
                $club->entry[ $event->id ] = true;
            }
        }

        $filename = ( ! empty( $template ) ) ? 'entry-league-' . $template : 'entry-league';

        return new Shortcode_View_Model(
            $filename,
            [
                'club'               => $club,
                'competition'        => $competition,
                'events'             => $events,
                'season'             => $season,
                'competition_season' => $competition_season,
            ],
            'entry'
        );
    }

    private function build_tournament_entry( Tournament $tournament, ?Player $player, string $template ): Shortcode_View_Model {
        $player_id     = $player ? $player->get_id() : 0;
        $tournament_id = $tournament->get_id();
        $season        = $tournament->get_season();

        if ( empty( $player?->year_of_birth ) ) {
            $player_age = 0;
        } else {
            $player_age = (int) substr( (string) $tournament->date_end, 0, 4 ) - intval( $player->year_of_birth );
        }

        $tournament->fees     = $this->tournament_service->get_fees( $tournament_id );
        $tournament->payments = $this->finance_service->get_tournament_paid_total_for_player( $player_id, $tournament_id );

        $player_entries = [];
        $events         = $this->tournament_service->get_events_for_tournament( $tournament_id );
        $c              = 0;

        foreach ( $events as $event ) {
            $entry_valid = false;
            $gender      = $player?->gender ?? '';

            if ( 'M' === $gender ) {
                if ( ! str_starts_with( $event->type, 'W' ) && ! str_starts_with( $event->type, 'G' ) ) {
                    $entry_valid = true;
                }
            } elseif ( 'F' === $gender ) {
                if ( ! str_starts_with( $event->type, 'M' ) && ! str_starts_with( $event->type, 'B' ) ) {
                    $entry_valid = true;
                }
            }

            if ( $entry_valid ) {
                if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
                    $entry_valid = true;
                } elseif ( empty( $player_age ) ) {
                    $entry_valid = false;
                } elseif ( $event->age_limit >= 30 ) {
                    $age_limit = $event->age_limit;
                    if ( 'F' === $gender && ! empty( $event->age_offset ) ) {
                        $age_limit = $event->age_limit - $event->age_offset;
                    }
                    if ( $player_age < $age_limit ) {
                        $entry_valid = false;
                    } else {
                        $entry_valid = true;
                    }
                } elseif ( $player_age > $event->age_limit ) {
                    $entry_valid = false;
                } else {
                    $entry_valid = true;
                }
            }

            if ( $entry_valid ) {
                $player_entry  = new stdClass();
                $entry_details = $this->tournament_service->get_player_entry_for_event( $event->get_id(), $player_id, $season );
                if ( $entry_details ) {
                    $player_entry->team_id = $entry_details->team_id;
                    if ( ! empty( $entry_details->partner_id ) ) {
                        $player_entry->partner_id   = $entry_details->partner_id;
                        $player_entry->partner_name = $entry_details->partner_name;
                    }
                    $player_entry->event          = $event->name;
                    $player_entries[ $event->id ] = $player_entry;
                }
            } else {
                unset( $events[ $c ] );
            }
            ++$c;
        }

        $club_memberships = $player_id ? $this->registration_service->get_clubs_for_player( $player_id ) : [];
        $tournament_entry = $player_id ? $this->competition_entry_service->get_tournament_entry_by_tournament_and_player( $tournament_id, $player_id ) : null;

        $filename = ( ! empty( $template ) ) ? 'entry-tournament-' . $template : 'entry-tournament';

        return new Shortcode_View_Model(
            $filename,
            [
                'tournament'       => $tournament,
                'events'           => $events,
                'player'           => $player,
                'player_entries'   => $player_entries,
                'club_memberships' => $club_memberships,
                'tournament_entry' => $tournament_entry,
                'season'           => $tournament->season,
            ],
            'entry'
        );
    }
}
