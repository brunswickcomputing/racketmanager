<?php
declare( strict_types=1 );
/**
 * Players_List_DTO API: Players_List_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Player;

use Racketmanager\Util\Util;
use function class_exists;
use function function_exists;
use function is_array;

/**
 * Class to implement the Players List Data Transfer Object
 */
class Players_List_DTO {
    public int $id;
    public int $ID;
    public string $firstname;
    public string $surname;
    public string $first_name;
    public string $last_name;
    public ?string $display_name;
    public string $index;
    public ?string $email;
    public ?string $link;
    public ?string $gender;
    public ?string $btm;
    public ?string $contactno;
    public ?string $user_nicename;

    public function __construct( object|array $data ) {
        if ( is_array( $data ) ) {
            $data = (object) $data;
        }

        $this->id           = (int) ( $data->player_id ?? ( $data->id ?? ( $data->ID ?? 0 ) ) );
        $this->ID           = $this->id;
        $this->display_name = $data->display_name ?? null;
        $this->firstname    = $data->firstName ?? ( $data->firstname ?? ( $data->first_name ?? '' ) );
        $this->surname      = $data->surname ?? ( $data->last_name ?? '' );
        $this->first_name   = $this->firstname;
        $this->last_name    = $this->surname;
        $this->email        = $data->email ?? ( $data->email_address ?? ( $data->user_email ?? null ) );
        $this->gender       = $data->gender ?? null;
        $this->btm          = $data->btm ?? ( $data->btm_number ?? null );
        $this->contactno     = $data->contactno ?? ( $data->contact_no ?? null );
        $this->user_nicename = $data->user_nicename ?? null;

        if ( isset( $data->index ) && '' !== $data->index ) {
            $this->index = (string) $data->index;
        } elseif ( '' !== $this->surname && '' !== $this->firstname ) {
            $this->index = $this->surname . ', ' . $this->firstname;
        } else {
            $this->index = $this->display_name ?? '';
        }

        if ( isset( $data->link ) && '' !== $data->link ) {
            $this->link = (string) $data->link;
        } elseif ( ! empty( $this->display_name ) ) {
            $this->link = \Racketmanager\Util\Player_Link_Util::generate_link( $this->display_name, $this->btm ?? null );
        } else {
            $this->link = null;
        }
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_display_name(): string {
        return $this->display_name ?? '';
    }
}
