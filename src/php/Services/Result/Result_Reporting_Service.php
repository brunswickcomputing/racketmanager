<?php
/**
 * Result Reporting Service
 *
 * @package Racketmanager\Services\Result
 */

namespace Racketmanager\Services\Result;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Repositories\Repository_Provider;
use stdClass;

/**
 * Class Result_Reporting_Service
 */
class Result_Reporting_Service {

    /**
     * Repository Provider
     *
     * @var Repository_Provider
     */
    private Repository_Provider $repository_provider;

    /**
     * Result_Reporting_Service constructor.
     *
     * @param Repository_Provider|null $repository_provider The repository provider.
     */
    public function __construct( ?Repository_Provider $repository_provider = null ) {
        $this->repository_provider = $repository_provider ?? new Repository_Provider();
    }

    /**
     * Report result for a match
     *
     * @param Fixture     $fixture          The fixture to report.
     * @param string|null $competition_code competition code (optional).
     *
     * @return object|null
     */
    public function report_result( Fixture $fixture, ?string $competition_code = null ): ?object {
        global $racketmanager;

        $league_repository      = $this->repository_provider->get_league_repository();
        $event_repository       = $this->repository_provider->get_event_repository();
        $competition_repository = $this->repository_provider->get_competition_repository();

        $league      = $league_repository->find_by_id( $fixture->get_league_id() );
        $event       = $event_repository->find_by_id( $league->get_event_id() );
        $competition = $competition_repository->find_by_id( $event->get_competition_id() );

        if ( empty( $competition_code ) ) {
            $competition_season = empty( $competition->get_season_by_name( $fixture->get_season() ) ) ? null : $competition->get_season_by_name( $fixture->get_season() );
            $competition_code   = empty( $competition_season['competition_code'] ) ? ( $competition->competition_code ?? null ) : $competition_season['competition_code'];
        }

        if ( empty( $competition_code ) ) {
            return null;
        }

        $result             = new stdClass();
        $result->tournament = $racketmanager->site_name . ' ' . $competition->name;
        $result->code       = $competition_code;
        $result->organiser  = '';
        $result->venue      = '';
        $result->event_name = $event->name;

        $event_season = empty( $event->get_season_by_name( $fixture->get_season() ) ) ? null : $event->get_season_by_name( $fixture->get_season() );
        $result->grade            = $event_season['grade'] ?? $competition->settings['grade'] ?? null;
        $result->event_end_date   = $competition->date_end;
        $result->event_start_date = $competition->date_start;

        $this->populate_event_details( $result, $event );
        $this->populate_draw_details( $result, $league, $event, $competition, $fixture );

        $result->matches = array();
        if ( ! empty( $league->num_rubbers ) ) {
            $this->process_rubbers( $result, $event, $fixture );
        } else {
            $this->process_fixture_match( $result, $event, $fixture );
        }

        return $result;
    }

    /**
     * Populate event details into the result object
     *
     * @param object $result Result object.
     * @param object $event  Event object.
     */
    private function populate_event_details( object $result, object $event ): void {
        $result->age_group = match ( $event->age_limit ) {
            '8', '9', '10', '11', '12', '14', '16', '18', '21'               => $event->age_limit . ' & Under',
            '30', '35', '40', '45', '50', '55', '60', '65', '70', '75', '80', '85' => $event->age_limit . ' & Over',
            default                                        => 'Open',
        };

        $result->event_type = 'Singles';
        if ( 'D' === substr( $event->type, 1, 1 ) ) {
            $result->event_type = 'Doubles';
        }

        if ( str_starts_with( $event->type, 'M' ) ) {
            $result->gender = 'Male';
        } elseif ( str_starts_with( $event->type, 'W' ) ) {
            $result->gender = 'Female';
        } else {
            $result->gender = 'Mixed';
        }
    }

    /**
     * Populate draw details into the result object
     *
     * @param object  $result      Result object.
     * @param object  $league      League object.
     * @param object  $event       Event object.
     * @param object  $competition Competition object.
     * @param Fixture $fixture     Fixture object.
     */
    private function populate_draw_details( object $result, object $league, object $event, object $competition, Fixture $fixture ): void {
        $result->draw_name = $league->title;
        if ( 'league' === $competition->type ) {
            $result->draw_type  = 'Round Robin';
            $result->draw_stage = 'MD - Main draw';
            $result->draw_size  = $league->num_teams_total;
            $result->round      = 'RR' . $fixture->get_match_day();
        } else {
            $result->draw_type  = 'Elimination';
            $result->draw_stage = $fixture->get_league_id() === $event->primary_league ? 'MD - Main draw' : 'CD - Consolation draw';
            $result->draw_size  = $league->championship->num_teams_first_round;
            $result->round      = match ( $fixture->get_final() ) {
                'final'   => 'F',
                'semi'    => 'SF',
                'quarter' => 'QF',
                'last-16' => 'R16',
                'last-32' => 'R32',
                'last-64' => 'R64',
                default   => 'RR1',
            };
        }
    }

    /**
     * Process rubbers for a fixture and add to result matches
     *
     * @param object  $result  Result object.
     * @param object  $event   Event object.
     * @param Fixture $fixture Fixture object.
     */
    private function process_rubbers( object $result, object $event, Fixture $fixture ): void {
        if ( $fixture->is_cancelled() || $fixture->is_shared() || $fixture->is_withdrawn() ) {
            return;
        }

        $rubber_repository = $this->repository_provider->get_rubber_repository();
        $rubbers           = $rubber_repository->find_by_fixture_id( $fixture->get_id() );

        foreach ( $rubbers as $rubber ) {
            if ( $rubber->is_walkover() || $rubber->is_shared() || empty( $rubber->get_winner_id() ) || empty( $rubber->get_loser_id() ) ) {
                continue;
            }

            $winner_id = $rubber->get_winner_id();
            if ( $rubber->get_status() === 0 ) {
                $winner_id = $this->calculate_rubber_winner( $rubber, $fixture );
            }

            if ( empty( $winner_id ) ) {
                continue;
            }

            $result_match        = new stdClass();
            $result_match->match = $rubber->get_id();

            $sides          = $this->identify_winning_and_losing_sides( $winner_id, $fixture );
            $winning_player = $sides['winning_player'];
            $losing_player  = $sides['losing_player'];

            $rubber->get_players();
            $this->populate_player_info( $result_match, $rubber, $event, $sides );

            $result_match->score      = '';
            $result_match->score_code = $rubber->get_status() === 3 ? 'Retired' : '';
            $result_match->match_date = mysql2date( 'Y-m-d', $fixture->get_date() );
            $result_match             = $this->report_result_scores( $result_match, $rubber->get_custom()['sets'] ?? [], $winning_player, $losing_player );
            $result->matches[]        = $result_match;
        }
    }

    /**
     * Identify winning and losing teams and players
     *
     * @param string  $winner_id Winner ID.
     * @param Fixture $fixture   Fixture object.
     *
     * @return array
     */
    private function identify_winning_and_losing_sides( string $winner_id, Fixture $fixture ): array {
        if ( $winner_id === (string) $fixture->get_home_team() ) {
            return [
                'winning_team'   => 'home',
                'winning_player' => 'player1',
                'losing_team'    => 'away',
                'losing_player'  => 'player2',
            ];
        }

        return [
            'winning_team'   => 'away',
            'winning_player' => 'player2',
            'losing_team'    => 'home',
            'losing_player'  => 'player1',
        ];
    }

    /**
     * Populate player info into the result match
     *
     * @param object $result_match Result match object.
     * @param object $rubber       Rubber object.
     * @param object $event        Event object.
     * @param array  $sides        Sides mapping.
     */
    private function populate_player_info( object $result_match, object $rubber, object $event, array $sides ): void {
        $winning_team = $sides['winning_team'];
        $losing_team  = $sides['losing_team'];

        $result_match->winner_name   = $rubber->players[ $winning_team ][ '1' ]->display_name ?? '';
        $result_match->winner_lta_no = $rubber->players[ $winning_team ][ '1' ]->btm ?? '';
        $result_match->loser_name    = $rubber->players[ $losing_team ][ '1' ]->display_name ?? '';
        $result_match->loser_lta_no  = $rubber->players[ $losing_team ][ '1' ]->btm ?? '';

        if ( 'D' === substr( $event->type, 1, 1 ) ) {
            $result_match->winnerpartner        = $rubber->players[ $winning_team ][ '2' ]->display_name ?? '';
            $result_match->winnerpartner_lta_no = $rubber->players[ $winning_team ][ '2' ]->btm ?? '';
            $result_match->loserpartner         = $rubber->players[ $losing_team ][ '2' ]->display_name ?? '';
            $result_match->loserpartner_lta_no  = $rubber->players[ $losing_team ][ '2' ]->btm ?? '';
        }
    }

    /**
     * Calculate rubber winner based on sets
     *
     * @param object  $rubber  Rubber object.
     * @param Fixture $fixture Fixture object.
     *
     * @return string|null
     */
    private function calculate_rubber_winner( object $rubber, Fixture $fixture ): ?string {
        $score_home = 0;
        $score_away = 0;
        foreach ( $rubber->get_custom()['sets'] ?? [] as $set ) {
            if ( $set['player1'] > $set['player2'] ) {
                ++$score_home;
            } elseif ( $set['player2'] > $set['player1'] ) {
                ++$score_away;
            }
        }
        if ( $score_home > $score_away ) {
            return $fixture->get_home_team();
        } elseif ( $score_away > $score_home ) {
            return $fixture->get_away_team();
        }
        return null;
    }

    /**
     * Process fixture match when no rubbers and add to result matches
     *
     * @param object  $result  Result object.
     * @param object  $event   Event object.
     * @param Fixture $fixture Fixture object.
     */
    private function process_fixture_match( object $result, object $event, Fixture $fixture ): void {
        if ( $fixture->is_walkover() || '-1' === (string) $fixture->get_home_team() || '-1' === (string) $fixture->get_away_team() ) {
            return;
        }

        $result_match        = new stdClass();
        $result_match->match = $fixture->get_id();

        if ( (string) $fixture->get_winner_id() === (string) $fixture->get_home_team() ) {
            $winning_team   = 'home';
            $winning_player = 'player1';
            $losing_team    = 'away';
            $losing_player  = 'player2';
        } else {
            $winning_team   = 'away';
            $winning_player = 'player2';
            $losing_team    = 'home';
            $losing_player  = 'player1';
        }

        $team_repository = $this->repository_provider->get_team_repository();
        $home_team       = $team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team       = $team_repository->find_by_id( (int) $fixture->get_away_team() );
        $teams           = [
            'home' => $home_team,
            'away' => $away_team,
        ];

        $result_match->winner_name          = $teams[ $winning_team ]->get_players()[ '1' ]->display_name ?? '';
        $result_match->winner_lta_no        = $teams[ $winning_team ]->get_players()[ '1' ]->btm ?? '';
        $result_match->loser_name           = $teams[ $losing_team ]->get_players()[ '1' ]->display_name ?? '';
        $result_match->loser_lta_no         = $teams[ $losing_team ]->get_players()[ '1' ]->btm ?? '';
        $result_match->winnerpartner        = '';
        $result_match->winnerpartner_lta_no = '';
        $result_match->loserpartner         = '';
        $result_match->loserpartner_lta_no  = '';

        if ( 'D' === substr( $event->type, 1, 1 ) ) {
            $result_match->winnerpartner        = $teams[ $winning_team ]->get_players()[ '2' ]->display_name ?? '';
            $result_match->winnerpartner_lta_no = $teams[ $winning_team ]->get_players()[ '2' ]->btm ?? '';
            $result_match->loserpartner         = $teams[ $losing_team ]->get_players()[ '2' ]->display_name ?? '';
            $result_match->loserpartner_lta_no  = $teams[ $losing_team ]->get_players()[ '2' ]->btm ?? '';
        }

        $result_match->score      = '';
        $result_match->match_date = mysql2date( 'Y-m-d', $fixture->get_date() );
        $result_match             = $this->report_result_scores( $result_match, $fixture->get_custom()['sets'] ?? [], $winning_player, $losing_player );
        $result_match->score_code = '';

        if ( $fixture->is_retired() ) {
            $result_match->score_code = 'R';
        } elseif ( empty( $result_match->score ) ) {
            $result_match->score_code = 'W';
        } elseif ( $fixture->is_shared() || $fixture->is_cancelled() ) {
            $result_match->score_code = 'N';
        }

        $result->matches[] = $result_match;
    }

    /**
     * Produce scores for reporting results
     *
     * @param object $result_match   match result object.
     * @param array  $sets           sets.
     * @param string $winning_player winning player reference.
     * @param string $losing_player  losing player reference.
     *
     * @return object updated result_match object.
     */
    private function report_result_scores( object $result_match, array $sets, string $winning_player, string $losing_player ): object {
        for ( $s = 1; $s <= 5; $s++ ) {
            $set = $sets[ $s ] ?? null;

            if ( $set instanceof Set_Score ) {
                $this->process_set_score_object( $result_match, $set, $s, $winning_player, $losing_player );
            } elseif ( is_array( $set ) && ( ! empty( $set[ $winning_player ] ) || ! empty( $set[ $losing_player ] ) ) ) {
                $this->process_set_score_array( $result_match, $set, $s, $winning_player, $losing_player );
            } else {
                $this->populate_empty_set( $result_match, $s );
            }
        }

        return $result_match;
    }

    /**
     * Process a Set_Score object and update result_match
     *
     * @param object    $result_match   Result match object.
     * @param Set_Score $set            Set score object.
     * @param int       $s              Set number.
     * @param string    $winning_player Winning player key.
     * @param string    $losing_player  Losing player key.
     */
    private function process_set_score_object( object $result_match, Set_Score $set, int $s, string $winning_player, string $losing_player ): void {
        $p1 = 'player1' === $winning_player ? $set->get_home_games() : $set->get_away_games();
        $p2 = 'player1' === $losing_player ? $set->get_home_games() : $set->get_away_games();
        $tb = 'player1' === $winning_player ? $set->get_home_tiebreak() : $set->get_away_tiebreak();

        if ( $s > 1 ) {
            $result_match->score .= ' ';
        }

        $result_match->score .= $p1 . '-' . $p2;
        if ( ! empty( $tb ) ) {
            $result_match->score           .= '(' . $tb . ')';
            $result_match->{'tiebreak' . $s} = $tb;
        } else {
            $result_match->{'tiebreak' . $s} = '';
        }

        $result_match->{'set' . $s . 'team1'} = $p1;
        $result_match->{'set' . $s . 'team2'} = $p2;
    }

    /**
     * Process a set score array and update result_match
     *
     * @param object $result_match   Result match object.
     * @param array  $set            Set score array.
     * @param int    $s              Set number.
     * @param string $winning_player Winning player key.
     * @param string $losing_player  Losing player key.
     */
    private function process_set_score_array( object $result_match, array $set, int $s, string $winning_player, string $losing_player ): void {
        if ( $s > 1 ) {
            $result_match->score .= ' ';
        }

        $match_tiebreak = false;
        if ( ( isset( $set['settype'] ) && 'MTB' === $set['settype'] ) || ( 3 === $s && '1' === (string) $set[ $winning_player ] && '0' === (string) $set[ $losing_player ] ) ) {
            $result_match->score .= '[';
            $match_tiebreak       = true;
        }

        if ( $match_tiebreak && ( empty( $set['settype'] ) || 'MTB' !== $set['settype'] ) ) {
            $set[ $winning_player ] = 10;
            $set[ $losing_player ]  = 8;
        }

        if ( '7' === (string) $set[ $winning_player ] && '6' === (string) $set[ $losing_player ] && empty( $set['tiebreak'] ) ) {
            $set['tiebreak'] = 5;
        }

        $result_match->score .= $set[ $winning_player ] . '-' . $set[ $losing_player ];
        if ( ! empty( $set['tiebreak'] ) ) {
            $result_match->score           .= '(' . $set['tiebreak'] . ')';
            $result_match->{'tiebreak' . $s} = $set['tiebreak'];
        } else {
            $result_match->{'tiebreak' . $s} = '';
        }

        if ( $match_tiebreak ) {
            $result_match->score .= ']';
        }

        $result_match->{'set' . $s . 'team1'} = $set[ $winning_player ];
        $result_match->{'set' . $s . 'team2'} = $set[ $losing_player ];
    }

    /**
     * Populate empty set values
     *
     * @param object $result_match Result match object.
     * @param int    $s            Set number.
     */
    private function populate_empty_set( object $result_match, int $s ): void {
        $result_match->{'set' . $s . 'team1'} = '';
        $result_match->{'set' . $s . 'team2'} = '';
        $result_match->{'tiebreak' . $s}     = '';
    }
}
