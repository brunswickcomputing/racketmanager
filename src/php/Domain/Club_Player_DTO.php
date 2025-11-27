<?php
/**
 * Club_Player DTO API: Club_Player_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

/**
 * Class to implement the Club Player Data Transfer Object
 */
class Club_Player_DTO {
    public ?int $user_id;
    public string $display_name;
    public ?string $email;
    public ?string $gender;
    public ?string $btm;
    public int $club_id;
    public string $club_name;
    public ?string $registration_date;
    public ?string $approval_date;
    public ?string $removal_date;
    public ?int $approved_by_user_id;
    public ?int $registered_by_user_id;
    public ?int $removed_by_user_id;
    public ?int $registration_id;
    public array $wtn;

    /**
     * Club_Player_DTO constructor.
     *
     * @param $data
     */
    public function __construct( $data ) {
        $this->registration_id       = $data->registration_id;
        $this->user_id               = $data->user_id;
        $this->display_name          = $data->display_name;
        $this->email                 = $data->email;
        $this->gender                = $data->gender;
        $this->btm                   = $data->btm;
        $this->club_id               = $data->club_id;
        $this->club_name             = $data->club_name;
        $this->registration_date     = $data->registration_date;
        $this->approval_date         = $data->approval_date;
        $this->removal_date          = $data->removal_date;
        $this->registered_by_user_id = $data->registered_by_user_id;
        $this->approved_by_user_id   = $data->approved_by_user_id;
        $this->removed_by_user_id    = $data->removed_by_user_id;
        $this->wtn                   = $data->wtn;
    }

}
