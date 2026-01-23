<?php
/**
 * Players_List_DTO API: Players_List_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Players List Data Transfer Object
 */
class Players_List_DTO {
    public int $id;
    public string $firstname;
    public string $surname;
    public string $display_name;
    public string $index;

    public function __construct( $data ) {
        $this->id           = (int) $data->playerId;
        $this->display_name = $data->display_name;
        $this->firstname    = $data->firstName ? : '';
        $this->surname      = $data->surname ? : '';
    }

}
