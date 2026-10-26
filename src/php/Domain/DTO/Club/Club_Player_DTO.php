<?php
declare( strict_types=1 );
/**
 * Club_Player DTO API: Club_Player_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Club;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Player\Player;

/**
 * Class to implement the Club Player Data Transfer Object
 */
class Club_Player_DTO {
    public ?int $user_id = null;
    public ?string $display_name = null;
    public ?string $email = null;
    public ?string $gender = null;
    public ?string $btm = null;
    public ?int $club_id = null;
    public ?string $club_name = null;
    public ?string $club_display_name = null;
    public ?string $registration_date = null;
    public ?string $approval_date = null;
    public ?string $removal_date = null;
    public ?int $approved_by_user_id = null;
    public ?int $registered_by_user_id = null;
    public ?int $removed_by_user_id = null;
    public ?int $registration_id = null;
    public array $wtn = array();
    public mixed $age = null;
    /**
     * @var mixed|string
     */
    public mixed $firstname = null;
    /**
     * @var mixed|string
     */
    public mixed $surname = null;
    public ?string $link = null;
    public ?int $year_of_birth = null;
    /**
     * @var mixed|string
     */
    public mixed $contactno = null;
    public ?int $id = null;
    public mixed $registered_by_user = null;
    public mixed $approved_by_user = null;
    public mixed $removed_by_user = null;
    public ?bool $system_record = null;
    /**
     * Error description variable
     *
     * @var string|null
     */
    public ?string $description = null;
    /**
     * Class
     *
     * @var string|null
     */
    public ?string $class = null;

    /**
     * Club_Player_DTO constructor.
     *
     * @param Player $player
     * @param Club $club
     * @param Club_Player $registration
     * @param mixed $registered_by
     * @param mixed $approved_by
     * @param mixed $removed_by
     */
    public function __construct( Player $player, Club $club, Club_Player $registration, mixed $registered_by = null, mixed $approved_by = null, mixed $removed_by = null ) {
        $this->registration_id       = $registration->id ?? null;
        $this->user_id               = $registration->player_id ?? null;
        $this->club_id               = $registration->club_id ?? null;
        $this->system_record         = $registration->system_record ?? null;
        $this->registration_date     = $registration->requested_date ?? null;
        $this->approval_date         = $registration->created_date ?? null;
        $this->removal_date          = $registration->removed_date ?? null;
        $this->registered_by_user_id = $registration->requested_user ?? null;
        $this->registered_by_user    = $registered_by;
        $this->approved_by_user      = $approved_by;
        $this->removed_by_user       = $removed_by;
        $this->approved_by_user_id   = $registration->created_user ?? null;
        $this->removed_by_user_id    = $registration->removed_user ?? null;
        $this->id                    = $player->id ?? null;
        $this->display_name          = $player->display_name ?? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : null );
        $this->firstname             = $player->firstname ?? null;
        $this->surname               = $player->surname ?? null;
        $this->link                  = $player->link ?? '';
        $this->year_of_birth         = $player->year_of_birth ?? null;
        $this->contactno             = $player->contactno ?? null;
        $this->email                 = $player->email ?? null;
        $this->gender                = $player->gender ?? null;
        $this->btm                   = ! empty( $player->btm ) ? (string) $player->btm : null;
        $this->wtn                   = is_array( $player->wtn ) ? $player->wtn : array();
        $this->age                   = $player->age ?? null;
        $this->club_name             = $club->shortcode ?? $club->name ?? '';
        $this->club_display_name     = $club->name ?? $club->shortcode ?? '';
    }

}
