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
    public mixed $age;
    /**
     * @var mixed|string
     */
    public mixed $firstname;
    /**
     * @var mixed|string
     */
    public mixed $surname;
    public string $link;
    public ?int $year_of_birth;
    /**
     * @var mixed|string
     */
    public mixed $contactno;
    public ?int $id;
    public mixed $registered_by_user;
    public mixed $approved_by_user;
    public mixed $removed_by_user;
    public ?bool $system_record;
    /**
     * Error description variable
     *
     * @var string|null
     */
    public ?string $description;
    /**
     * Class
     *
     * @var string|null
     */
    public ?string $class;

    /**
     * Club_Player_DTO constructor.
     *
     * @param Player $player
     * @param Club $club
     * @param Club_Player $registration
     * @param $registered_by
     * @param $approved_by
     * @param $removed_by
     */
    public function __construct( Player $player, Club $club, Club_Player $registration, $registered_by, $approved_by, $removed_by ) {
        $this->registration_id       = $registration->id;
        $this->user_id               = $registration->player_id;
        $this->club_id               = $registration->club_id;
        $this->system_record         = $registration->system_record;
        $this->registration_date     = $registration->requested_date;
        $this->approval_date         = $registration->created_date;
        $this->removal_date          = $registration->removed_date;
        $this->registered_by_user_id = $registration->requested_user;
        $this->registered_by_user    = $registered_by;
        $this->approved_by_user      = $approved_by;
        $this->removed_by_user       = $removed_by;
        $this->approved_by_user_id   = $registration->created_user;
        $this->removed_by_user_id    = $registration->removed_user;
        $this->id                    = $player->id;
        $this->display_name          = $player->display_name;
        $this->firstname             = $player->firstname;
        $this->surname               = $player->surname;
        $this->link                  = $player->link;
        $this->year_of_birth         = $player->year_of_birth;
        $this->contactno             = $player->contactno;
        $this->email                 = $player->email;
        $this->gender                = $player->gender;
        $this->btm                   = $player->btm;
        $this->wtn                   = $player->wtn;
        $this->age                   = $player->age;
        $this->club_name             = $club->shortcode;
    }

}
