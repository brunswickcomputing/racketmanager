<?php
/**
 * Exporter API: exporter
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Exporter
 */

namespace Racketmanager\Services;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Services\Validator\Validator;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_results_report;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\seo_url;
use function Racketmanager\un_seo_url;

/**
 * Class to implement the Exporter object
 */
class Exporter {
    /**
     * Calendar export function
     */
    public function calendar(): void {
        global $racketmanager;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['league_id'] ) && isset( $_GET['season'] ) ) {
            $league_id      = sanitize_text_field( wp_unslash( $_GET['league_id'] ) );
            $league         = get_league( $league_id );
            $season         = sanitize_text_field( wp_unslash( $_GET['season'] ) );
            $match_array    = array(
                'season'    => $season,
                'match_day' => -1,
                'limit'     => false,
            );
            $file_team_name = '';
            if ( isset( $_GET['team_id'] ) ) {
                $team_id                = sanitize_text_field( wp_unslash( $_GET['team_id'] ) );
                $team                   = get_team( $team_id );
                $file_team_name         = '-' . seo_url( $team->title );
                $match_array['team_id'] = $team_id;
            }
            $matches  = $league->get_matches( $match_array );
            $filename = $season . '-' . sanitize_title( $league->title ) . $file_team_name . '.ics';
            $this->output_calendar( $matches, $filename );
        } elseif ( isset( $_GET['competition_id'] ) ) {
            $competition_id = sanitize_text_field( wp_unslash( $_GET['competition_id'] ) );
            $competition    = get_competition( $competition_id );
            $season         = $competition->get_season();
            $match_array    = array(
                'competition_id' => $competition_id,
                'season'         => $season,
            );
            $file_club_name = '';
            if ( isset( $_GET['club_id'] ) ) {
                $club_id             = sanitize_text_field( wp_unslash( $_GET['club_id'] ) );
                $club                = get_club( $club_id );
                $file_club_name      = '-' . seo_url( $club->name );
                $match_array['club'] = $club_id;
            }
            $matches  = $racketmanager->get_matches( $match_array );
            $filename = $season . '-' . sanitize_title( $competition->name ) . $file_club_name . '.ics';
            $this->output_calendar( $matches, $filename );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }
    /**
     * Export results function
     *
     * Optional parameters:
     *  - club
     *  - days (defaults to 7)
     *  - competition
     */
    #[NoReturn]
    public function results(): void {
        global $racketmanager;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['club'] ) ) {
            if ( is_numeric( $_GET['club'] ) ) {
                $club_id = intval( $_GET['club'] );
                $club    = get_club( $club_id );

            } else {
                $club_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['club'] ) ) );
                $club      = get_club( $club_name, 'shortcode' );
                $club_id   = $club->id;
            }
        } else {
            $club    = '';
            $club_id = '';
        }
        if ( isset( $_GET['days'] ) ) {
            $days = intval( $_GET['days'] );
        } else {
            $days = 7;
        }
        if ( isset( $_GET['competition'] ) ) {
            $competition = un_seo_url( sanitize_text_field( wp_unslash( $_GET['competition'] ) ) );
        } else {
            $competition = null;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $time    = 'latest';
        $matches = $racketmanager->get_matches(
            array(
                'days'             => $days,
                'competition_name' => $competition,
                'time'             => $time,
                'history'          => $days,
                'club'             => $club_id,
            )
        );
        $this->match_output( $club, $matches );
    }
    /**
     * Export fixtures function
     *
     * Required parameters:
     *  competition
     *  season
     * Optional parameters:
     *  club
     *  days - defaults to 7
     */
    #[NoReturn]
    public function fixtures(): void {
        global $racketmanager;
        $validator   = new Validator();
        $competition = null;
        $season      = null;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['club'] ) ) {
            if ( is_numeric( $_GET['club'] ) ) {
                $club_id = intval( $_GET['club'] );
                $club    = get_club( $club_id );
            } else {
                $club_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['club'] ) ) );
                $club      = get_club( $club_name, 'shortcode' );
                $club_id   = $club->id;
            }
            if ( ! $club ) {
                $validator = $validator->club( $club );
            }
        } else {
            $club    = '';
            $club_id = '';
        }
        if ( isset( $_GET['competition'] ) ) {
            $competition = un_seo_url( sanitize_text_field( wp_unslash( $_GET['competition'] ) ) );
        } else {
            $validator = $validator->competition( null );
        }
        if ( isset( $_GET['season'] ) ) {
            $season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
        } else {
            $validator = $validator->season( null );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( ! $validator->error ) {
            $matches = $racketmanager->get_matches(
                array(
                    'competition_name' => $competition,
                    'season'           => $season,
                    'club'             => $club_id,
                )
            );
            $this->match_output( $club, $matches );
        } else {
            $message = __( 'Error with export', 'racketmanager' );
            foreach ( $validator->error_msg as $err_msg ) {
                $message .= '<br />' . $err_msg;
            }
            echo wp_kses( $message, array( 'br' => array() ) );
            exit();
        }
    }
    /**
     * Produce calendar download file
     *
     * @param array $matches array of matches to download.
     * @param string $filename filename to be created.
     */
    #[NoReturn]
    private function output_calendar(array $matches, string $filename ): void {
        $date_format = 'Ymd\THis';

        $contents  = "BEGIN:VCALENDAR\n";
        $contents .= "VERSION:2.0\n";
        $contents .= "PRODID:-//TENNIS CALENDAR//NONSGML Events //EN\n";
        $contents .= "CALSCALE:GREGORIAN\n";
        $contents .= 'DTSTAMP:' . gmdate( $date_format ) . "\n";
        foreach ( $matches as $match ) {
            $match     = get_match( $match->id );
            $contents .= "BEGIN:VEVENT\n";
            $contents .= 'UID:' . $match->id . "\n";
            $contents .= 'DTSTAMP:' . mysql2date( $date_format, $match->date ) . "\n";
            $contents .= 'DTSTART:' . mysql2date( $date_format, $match->date ) . "\n";
            $contents .= 'DTEND:' . gmdate( $date_format, strtotime( '+2 hours', strtotime( $match->date ) ) ) . "\n";
            $contents .= 'SUMMARY:' . $match->match_title . "\n";
            $contents .= 'LOCATION:' . $match->location . "\n";
            $contents .= "END:VEVENT\n";
        }
        $contents .= 'END:VCALENDAR';
        header( 'Content-Type: text/calendar' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo esc_html( $contents );
        exit();
    }
    /**
     * Produce match output data
     *
     * @param object $club club object.
     * @param array $matches array of matches to download.
     */
    #[NoReturn]
    private function match_output( object $club, array $matches ): void {
        $contents = '[';
        $i        = 0;
        foreach ( $matches as $match ) {
            $json_result = new stdClass();
            if ( ! empty( $club ) ) {
                $json_result->club = str_replace( '"', '', $club->shortcode );
            }
            $json_result->home_team  = str_replace( '"', '', $match->teams['home']->title );
            $json_result->away_team  = str_replace( '"', '', $match->teams['away']->title );
            $json_result->match_date = substr( $match->date, 0, 10 );
            $json_result->match_time = $match->start_time;
            if ( $match->winner_id ) {
                $json_result->score = str_replace( '"', '', $match->score );
            }
            if ( $i ) {
                $contents .= ',';
            }
            $contents .= wp_json_encode( $json_result ) . "\n";
            ++$i;
        }
        $contents .= ']';
        header( 'Content-Type: application/json; charset=utf-8' );
        echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit();
    }
    /**
     * Report results
     */
    #[NoReturn]
    public function report_results(): void {
        global $racketmanager, $wpdb;
        $contents  = 'Tournament';
        $contents .= ',"Code"';
        $contents .= ',"Organiser"';
        $contents .= ',"Venue"';
        $contents .= ',"Event Name"';
        $contents .= ',"Grade"';
        $contents .= ',"Event Start Date"';
        $contents .= ',"Event End Date"';
        $contents .= ',"Age Group"';
        $contents .= ',"Event Type"';
        $contents .= ',"Gender"';
        $contents .= ',"Draw Name"';
        $contents .= ',"Draw Type"';
        $contents .= ',"Draw Stage"';
        $contents .= ',"Draw Size"';
        $contents .= ',"Round"';
        $contents .= ',"Match"';
        $contents .= ',"Winner Name"';
        $contents .= ',"Winner LTA No"';
        $contents .= ',"WinnerPartner"';
        $contents .= ',"WinnerPartner LTA No"';
        $contents .= ',"Loser Name"';
        $contents .= ',"Loser LTA No"';
        $contents .= ',"LoserPartner"';
        $contents .= ',"LoserPartner LTA No"';
        $contents .= ',"Score"';
        $contents .= ',"Score Code"';
        $contents .= ',"Match Date"';
        $contents .= ',"Team1Set1"';
        $contents .= ',"Team1Set2"';
        $contents .= ',"Team2Set1"';
        $contents .= ',"Team2Set2"';
        $contents .= ',"Team3Set1"';
        $contents .= ',"Team3Set2"';
        $contents .= ',"Team4Set1"';
        $contents .= ',"Team4Set2"';
        $contents .= ',"Team5Set1"';
        $contents .= ',"Team5Set2"';
        $contents .= ',"Tiebreak1"';
        $contents .= ',"Tiebreak2"';
        $contents .= ',"Tiebreak3"';
        $contents .= ',"Tiebreak4"';
        $contents .= ',"Tiebreak5"';
        $contents .= "\n";
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tournament_id    = isset( $_GET['tournament_id'] ) ? intval( $_GET['tournament_id'] ) : null;
        $competition_id   = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $event_id         = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
        $season           = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $match_day        = isset( $_GET['match_day'] ) ? intval( $_GET['match_day'] ) : null;
        $latest           = isset( $_GET['latest'] );
        $competition_code = isset( $_GET['competition_code'] ) ? sanitize_text_field( wp_unslash( $_GET['competition_code'] ) ) : null;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $filename = 'report-results';
        if ( $latest ) {
            $filename      .= '-latest';
            $latest_results = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT `id` FROM $wpdb->racketmanager_results_report ORDER BY `id`"
            );
            foreach ( $latest_results as $result ) {
                $result_report = get_results_report( $result->id );
                $contents     .= $this->result_data( $result_report->data );
                $result_report->delete();
            }
        } else {
            $match_args = array();
            if ( $competition_id ) {
                $match_args['competition_id'] = $competition_id;
                $competition                  = get_competition( $competition_id );
                $filename                    .= '-' . seo_url( $competition->name );
            } elseif ( $event_id ) {
                $match_args['event_id'] = $event_id;
                $event                  = get_event( $event_id );
                $filename              .= '-' . seo_url( $event->name );
            } elseif ( $tournament_id ) {
                $match_args['tournament_id'] = $tournament_id;
                $tournament                  = get_tournament( $tournament_id );
                $filename                   .= '-' . seo_url( $tournament->name );
            }
            if ( $season ) {
                $match_args['season'] = $season;
                $filename            .= '-' . $season;
            }
            if ( $match_day ) {
                $match_args['match_day'] = $match_day;
                $filename               .= '-' . $match_day;
            }
            $match_args['time'] = 'latest';
            $matches            = $racketmanager->get_matches( $match_args );
            foreach ( $matches as $match ) {
                $match   = get_match( $match );
                $results = $match->report_result( $competition_code );
                if ( $results ) {
                    $contents .= $this->result_data( $results );
                }
            }
        }
        $filename .= '.csv';
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: inline; filename="' . $filename . '"' );
        echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit();
    }
    /**
     * Format result data for export
     *
     * @param object $results result data.
     * @return string
     */
    private function result_data(object $results ): string {
        $contents         = '';
        $common_contents  = $results->tournament;
        $common_contents .= ',' . $results->code;
        $common_contents .= ',' . $results->organiser;
        $common_contents .= ',' . $results->venue;
        $common_contents .= ',' . $results->event_name;
        $common_contents .= ',' . $results->grade;
        $common_contents .= ',' . $results->event_start_date;
        $common_contents .= ',' . $results->event_end_date;
        $common_contents .= ',' . $results->age_group;
        $common_contents .= ',' . $results->event_type;
        $common_contents .= ',' . $results->gender;
        $common_contents .= ',' . $results->draw_name;
        $common_contents .= ',' . $results->draw_type;
        $common_contents .= ',' . $results->draw_stage;
        $common_contents .= ',' . $results->draw_size;
        $common_contents .= ',' . $results->round;
        foreach ( $results->matches as $result ) {
            $match_contents  = $common_contents;
            $match_contents .= ',' . $result->match;
            $match_contents .= ',' . $result->winner_name;
            $match_contents .= ',' . $result->winner_lta_no;
            $match_contents .= ',' . $result->winnerpartner;
            $match_contents .= ',' . $result->winnerpartner_lta_no;
            $match_contents .= ',' . $result->loser_name;
            $match_contents .= ',' . $result->loser_lta_no;
            $match_contents .= ',' . $result->loserpartner;
            $match_contents .= ',' . $result->loserpartner_lta_no;
            $match_contents .= ',' . $result->score;
            $match_contents .= ',' . $result->score_code;
            $match_contents .= ',' . $result->match_date;
            $match_contents .= ',' . $result->set1team1;
            $match_contents .= ',' . $result->set1team2;
            $match_contents .= ',' . $result->set2team1;
            $match_contents .= ',' . $result->set2team2;
            $match_contents .= ',' . $result->set3team1;
            $match_contents .= ',' . $result->set3team2;
            $match_contents .= ',' . $result->set4team1;
            $match_contents .= ',' . $result->set4team2;
            $match_contents .= ',' . $result->set5team1;
            $match_contents .= ',' . $result->set5team2;
            $match_contents .= ',' . $result->tiebreak1;
            $match_contents .= ',' . $result->tiebreak2;
            $match_contents .= ',' . $result->tiebreak3;
            $match_contents .= ',' . $result->tiebreak4;
            $match_contents .= ',' . $result->tiebreak5;
            $match_contents .= "\n";
            $contents       .= $match_contents;
        }
        return $contents;
    }
}
