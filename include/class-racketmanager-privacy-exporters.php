<?php
/**
 * Personal data exporters.
 *
 * @since 3.4.0
 * @package Racketmanager\Classes
 */

namespace Racketmanager;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Racketmanager_Privacy_Exporters Class.
 */
class Racketmanager_Privacy_Exporters {
    /**
     * Finds and exports user data by email address.
     *
     * @param string $email_address The user email address.
     *
     * @return array An array of personal data in name value pairs
     */
    public static function user_data_exporter( string $email_address ): array {
        $user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
        $data_to_export = array();
        if ( $user instanceof WP_User ) {
            $user_personal_data = self::get_user_personal_data( $user );
            if ( ! empty( $user_personal_data ) ) {
                $data_to_export[] = array(
                    'group_id'          => 'racketmanager_user',
                    'group_label'       => __( 'User Data', 'racketmanager' ),
                    'group_description' => __( 'User&#8217;s User data.', 'racketmanager' ),
                    'item_id'           => 'user',
                    'data'              => $user_personal_data,
                );
            }
        }
        return array(
            'data' => $data_to_export,
            'done' => true,
        );
    }
    /**
     * Get personal data (key/value pairs) for a user object.
     *
     * @param WP_User $user user object.
     *
     * @return array
     *@since 3.4.0
     */
    protected static function get_user_personal_data( WP_User $user ): array {
        $personal_data   = array();
        $user_meta       = get_user_meta( $user->ID );
        $props_to_export = array(
            'gender'        => __( 'Gender', 'racketmanager' ),
            'year_of_birth' => __( 'Year of birth', 'racketmanager' ),
            'btm'           => __( 'LTA Tennis Number', 'racketmanager' ),
            'remove_date'   => __( 'User Removed Date', 'racketmanager' ),
            'contactno'     => __( 'Telephone Number', 'racketmanager' ),
        );
        foreach ( $props_to_export as $prop => $description ) {
            $value = $user_meta[ $prop ][0] ?? '';
            if ( $value ) {
                $personal_data[] = array(
                    'name'  => $description,
                    'value' => $value,
                );
            }
        }
        return $personal_data;
    }
}
