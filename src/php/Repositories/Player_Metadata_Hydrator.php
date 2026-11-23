<?php
/**
 * Player metadata hydrator collaborator.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Util\Util_Lookup;

/**
 * Hydrates WordPress user data objects with player metadata.
 */
class Player_Metadata_Hydrator {
    public const string META_KEY_GENDER = 'gender';
    public const string META_KEY_BTM    = 'btm';
    public const string META_KEY_YOB    = 'year_of_birth';

    /**
     * Hydrate user data object with player metadata.
     *
     * @param object $data WordPress user data object.
     *
     * @return void
     */
    public function hydrate( object $data ): void {
        $this->hydrate_basic_meta( $data );
        $this->hydrate_removal_meta( $data );
        $this->hydrate_locked_meta( $data );
        $data->system_record = get_user_meta( $data->ID, 'racketmanager_type', true );
        $this->hydrate_wtn_meta( $data );
        $data->opt_ins = get_user_meta( $data->ID, 'racketmanager_opt_in', true );
    }

    /**
     * Hydrate basic identity and contact metadata on the user data object.
     *
     * @param object $data WordPress user data object.
     *
     * @return void
     */
    public function hydrate_basic_meta( object $data ): void {
        $data->firstname     = get_user_meta( $data->ID, 'first_name', true );
        $data->surname       = get_user_meta( $data->ID, 'last_name', true );
        $data->gender        = get_user_meta( $data->ID, self::META_KEY_GENDER, true );
        $data->type          = get_user_meta( $data->ID, 'racketmanager_type', true );
        $data->btm           = get_user_meta( $data->ID, self::META_KEY_BTM, true );
        $year_of_birth       = get_user_meta( $data->ID, self::META_KEY_YOB, true );
        $data->year_of_birth = empty( $year_of_birth ) ? null : $year_of_birth;
        $contact_no          = get_user_meta( $data->ID, 'contactno', true );
        $data->contactno     = empty( $contact_no ) ? null : $contact_no;
    }

    /**
     * Hydrate removal metadata on the user data object.
     *
     * @param object $data WordPress user data object.
     *
     * @return void
     */
    public function hydrate_removal_meta( object $data ): void {
        $removed_date       = get_user_meta( $data->ID, 'remove_date', true );
        $data->removed_date = null;
        $data->removed_user = null;
        if ( empty( $removed_date ) ) {
            return;
        }

        $data->removed_date = $removed_date;
        $removed_user       = get_user_meta( $data->ID, 'remove_user', true );
        if ( ! empty( $removed_user ) ) {
            $data->removed_user = $removed_user;
        }
    }

    /**
     * Hydrate account lock metadata on the user data object.
     *
     * @param object $data WordPress user data object.
     *
     * @return void
     */
    public function hydrate_locked_meta( object $data ): void {
        $locked       = get_user_meta( $data->ID, 'locked', true );
        $data->locked = ! empty( $locked );
        if ( ! $data->locked ) {
            return;
        }

        $data->locked_date = get_user_meta( $data->ID, 'locked_date', true );
        $data->locked_user = get_user_meta( $data->ID, 'locked_user', true );
        if ( empty( $data->locked_user ) ) {
            return;
        }

        $locked_user_data       = get_userdata( (int) $data->locked_user );
        $data->locked_user_name = $locked_user_data?->display_name ?? '';
    }

    /**
     * Hydrate WTN ratings on the user data object.
     *
     * @param object $data WordPress user data object.
     *
     * @return void
     */
    public function hydrate_wtn_meta( object $data ): void {
        $match_types = Util_Lookup::get_match_types();
        foreach ( $match_types as $match_type => $description ) {
            $wtn_type                 = 'wtn_' . $match_type;
            $data->wtn[ $match_type ] = get_user_meta( $data->ID, $wtn_type, true );
        }
    }
}
