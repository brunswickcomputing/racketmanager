<?php
/**
 * Tournament_Request_DTO API: Tournament_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO;

use stdClass;

/**
 * Class to implement the Tournament Request Data Transfer Object
 */
readonly class Tournament_Request_DTO {
    public ?int $id;
    public ?int $competition_id;
    public ?string $season;
    public ?string $name;
    public ?int $venue;
    public ?string $date_open;
    public ?string $date_closing;
    public ?string $date_withdrawal;
    public ?string $date_start;
    public ?string $date;
    public ?string $start_time;
    public ?string $competition_code;
    public ?string $grade;
    public ?int $num_entries;
    public stdClass $fees;

    /**
     * Cup_Entry_Request_DTO constructor.
     *
     * @param array $data
     */
    public function __construct( array $data ) {
        $this->id               = isset( $data['tournament_id'] ) ? absint( $data['tournament_id'] ) : null;
        $this->name             = isset( $data['tournamentName'] ) ? sanitize_text_field( wp_unslash( $data['tournamentName'] ) ) : null;
        $this->competition_id   = isset( $data['competition_id'] ) ? absint( $data['competition_id'] ) : null;
        $this->season           = isset( $data['season'] ) ? sanitize_text_field( wp_unslash( $data['season'] ) ) : null;
        $this->venue            = isset( $data['venue'] ) ? absint( $data['venue'] ) : null;
        $this->date_open        = isset( $data['dateOpen'] ) ? sanitize_text_field( wp_unslash( $data['dateOpen'] ) ) : null;
        $this->date_closing     = isset( $data['dateClose'] ) ? sanitize_text_field( wp_unslash( $data['dateClose'] ) ) : null;
        $this->date_withdrawal  = isset( $data['dateWithdraw'] ) ? sanitize_text_field( wp_unslash( $data['dateWithdraw'] ) ) : null;
        $this->date_start       = isset( $data['dateStart'] ) ? sanitize_text_field( wp_unslash( $data['dateStart'] ) ) : null;
        $this->date             = isset( $data['dateEnd'] ) ? sanitize_text_field( wp_unslash( $data['dateEnd'] ) ) : null;
        $this->start_time       = isset( $data['startTime'] ) ? sanitize_text_field( wp_unslash( $data['startTime'] ) ) : null;
        $this->competition_code = isset( $data['competition_code'] ) ? sanitize_text_field( wp_unslash( $data['competition_code'] ) ) : null;
        $this->grade            = isset( $data['grade'] ) ? sanitize_text_field( wp_unslash( $data['grade'] ) ) : null;
        $this->num_entries      = isset( $data['num_entries'] ) ? intval( $data['num_entries'] ) : null;
        $this->fees             = $this->get_fees_data( $data );
    }

    /**
     * Get fees data from input
     *
     * @param array $data
     *
     * @return stdClass
     */
    private function get_fees_data( array $data ): stdClass {
        $fees              = new stdClass();
        $fees->competition = isset( $data['feeCompetition'] ) ? floatval( $data['feeCompetition'] ) : null;
        $fees->event       = isset( $data['feeEvent'] ) ? floatval( $data['feeEvent'] ) : null;
        $fees->id          = isset( $data['feeId'] ) ? intval( $data['feeId'] ) : null;
        return $fees;
    }

}
