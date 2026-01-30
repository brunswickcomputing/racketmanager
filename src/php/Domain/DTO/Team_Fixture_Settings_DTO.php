<?php
/**
 * Team_Fixture_Settings_DTO API: Team_Fixture_Settings_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Class to implement the Team Fixture Settings Data Transfer Object
 */
class Team_Fixture_Settings_DTO {
    public $captain_id;
    public $captain_name;
    public $captain_email;
    public $captain_contact_no;
    public $match_day;
    public int $match_day_num;
    public $match_time;

    /**
     * Team_Details_DTO constructor.
     *
     * @param stdClass $data
     */
    public function __construct( stdClass $data ) {
        $this->captain_id         = $data->captain_id;
        $this->captain_name       = $data->captain_name;
        $this->captain_email      = $data->captain_email;
        $this->captain_contact_no = $data->captain_contact_no;
        $this->match_day          = $data->match_day;
        $this->match_day_num      = Util_Lookup::get_match_day_number( $data->match_day );
        $this->match_time         = $data->match_time;
    }

}
