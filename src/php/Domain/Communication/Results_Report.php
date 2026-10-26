<?php
declare( strict_types=1 );
/**
 * Results_Report API: Results_Report class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Results_Report
 */

namespace Racketmanager\Domain\Communication;

use Racketmanager\Domain\Value_Objects\Json_Encoder;

/**
 * Class to implement the results report object
 */
class Results_Report {
    /**
     * Id
     *
     * @var int
     */
    public int $id = 0;
    /**
     * Season
     *
     * @var int
     */
    public int $match_id;
    /**
     * Results report object stored in database as string
     *
     * @var string|false
     */
    public string|false $result_object;
    /**
     * Results report object
     *
     * @var object
     */
    public mixed $data;

    /**
     * Construct class instance
     *
     * @param object|null $results_report results_report object.
     */
    public function __construct( ?object $results_report = null ) {
        if ( ! is_null( $results_report ) ) {
            foreach ( get_object_vars( $results_report ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->data ) && isset( $this->result_object ) ) {
                $this->data = json_decode( $this->result_object );
            }
            if ( ! isset( $this->result_object ) && isset( $this->data ) ) {
                $this->result_object = Json_Encoder::encode( $this->data );
            }
        }
    }
}
