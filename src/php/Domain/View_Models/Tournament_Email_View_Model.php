<?php
/**
 * Tournament Email View Model
 *
 * @package RacketManager
 * @subpackage Domain/View_Models
 */

namespace Racketmanager\Domain\View_Models;

use Racketmanager\Domain\Tournament;

readonly class Tournament_Email_View_Model {

    public string $date_display;
    public string $date_closing_display;
    public string $date_withdrawal_display;
    public string $date_open_display;
    public string $date_start_display;

    public function __construct( Tournament $tournament ) {
        global $racketmanager;
        $this->date_display            = empty( $tournament->date_end ) ? 'N/A' : mysql2date( $racketmanager->date_format, $tournament->date_end );
        $this->date_closing_display    = empty( $tournament->date_closing ) ? 'N/A' : mysql2date( $racketmanager->date_format, $tournament->date_closing );
        $this->date_withdrawal_display = empty( $tournament->date_withdrawal ) ? 'N/A' : mysql2date( $racketmanager->date_format, $tournament->date_withdrawal );
        $this->date_open_display       = empty( $tournament->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $tournament->date_open );
        $this->date_start_display      = empty( $tournament->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $tournament->date_start );
    }
}
