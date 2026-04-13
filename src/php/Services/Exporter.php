<?php
/**
 * Exporter API: exporter
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Exporter
 */

namespace Racketmanager\Services;

use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Export\Formatters\Csv_Formatter;
use Racketmanager\Services\Export\Formatters\Ics_Formatter;
use Racketmanager\Services\Export\Formatters\Json_Formatter;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

/**
 * Class to implement the Exporter object
 */
class Exporter {

    private Fixture_Repository_Interface $fixture_repository;
    private Result_Reporting_Service $result_reporting_service;
    private Fixture_Detail_Service $fixture_detail_service;
    private Club_Repository_Interface $club_repository;

    public function __construct(
        Fixture_Repository_Interface $fixture_repository,
        Result_Reporting_Service $result_reporting_service,
        Fixture_Detail_Service $fixture_detail_service,
        Club_Repository_Interface $club_repository
    ) {
        $this->fixture_repository       = $fixture_repository;
        $this->result_reporting_service = $result_reporting_service;
        $this->fixture_detail_service   = $fixture_detail_service;
        $this->club_repository          = $club_repository;
    }

    /**
     * Calendar export function
     */
    public function calendar( Export_Criteria $criteria ): string {
        $fixtures = $this->fixture_repository->find_by_criteria( $criteria );
        $data     = array();

        foreach ( $fixtures as $fixture ) {
            $data[] = $this->prepare_calendar_row( $fixture );
        }

        $formatter = new Ics_Formatter();
        return $formatter->format( $data );
    }

    /**
     * Prepares a row for the calendar export.
     */
    private function prepare_calendar_row( $fixture ): array {
        $details = $this->fixture_detail_service->get_fixture_with_details( $fixture, true );

        return array(
            'id'       => $fixture->id,
            'date'     => $fixture->date,
            'title'    => $details ? $details->fixture_title : 'Fixture ' . $fixture->id,
            'location' => $fixture->location,
        );
    }

    /**
     * Export results function
     */
    public function results( Export_Criteria $criteria ): string {
        $fixtures = $this->fixture_repository->find_by_criteria( $criteria );
        $data     = array();

        foreach ( $fixtures as $fixture ) {
            $data[] = $this->prepare_result_row( $fixture, $criteria );
        }

        $formatter = ( 'csv' === $criteria->format ) ? new Csv_Formatter() : new Json_Formatter();
        return $formatter->format( $data );
    }

    /**
     * Prepares a row for the result export.
     */
    private function prepare_result_row( $fixture, Export_Criteria $criteria ): stdClass {
        $details = $this->fixture_detail_service->get_fixture_with_details( $fixture );
        $row     = new stdClass();

        if ( $criteria->club_id ) {
            $club      = $this->club_repository->find_by_id( $criteria->club_id );
            $row->club = $club ? $club->get_shortcode() : '';
        }

        $row->home_team  = $details && $details->home_team ? $details->home_team->team->title : '';
        $row->away_team  = $details && $details->away_team ? $details->away_team->team->title : '';
        $row->match_date = substr( $fixture->date, 0, 10 );
        $row->match_time = $fixture->start_time;

        if ( $fixture->winner_id ) {
            $row->score = $details ? $details->score_display : '';
        }

        return $row;
    }

    /**
     * Export fixtures function
     */
    public function fixtures( Export_Criteria $criteria ): string {
        return $this->results( $criteria );
    }

    /**
     * Report results
     */
    public function report_results( Export_Criteria $criteria ): string {
        $fixtures = $this->fixture_repository->find_by_criteria( $criteria );
        $data     = $this->result_reporting_service->report_fixtures( $fixtures );

        $formatter = new Csv_Formatter();
        $options   = array(
            'headers' => $this->get_report_headers(),
        );

        return $formatter->format( $data, $options );
    }

    /**
     * Gets the headers for the report results export.
     */
    private function get_report_headers(): array {
        return array(
            'Tournament',
            'Code',
            'Organiser',
            'Venue',
            'Event Name',
            'Grade',
            'Event Start Date',
            'Event End Date',
            'Age Group',
            'Event Type',
            'Gender',
            'Draw Name',
            'Draw Type',
            'Draw Stage',
            'Draw Size',
            'Round',
            'Match',
            'Winner Name',
            'Winner LTA No',
            'WinnerPartner',
            'WinnerPartner LTA No',
            'Loser Name',
            'Loser LTA No',
            'LoserPartner',
            'LoserPartner LTA No',
            'Score',
            'Score Code',
            'Match Date',
            'Team1Set1',
            'Team1Set2',
            'Team2Set1',
            'Team2Set2',
            'Team3Set1',
            'Team3Set2',
            'Team4Set1',
            'Team4Set2',
            'Team5Set1',
            'Team5Set2',
            'Tiebreak1',
            'Tiebreak2',
            'Tiebreak3',
            'Tiebreak4',
            'Tiebreak5',
        );
    }
}
