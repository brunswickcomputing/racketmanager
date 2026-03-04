<?php
/**
 * Mapper: legacy Validator_* -> Error_Bag
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Error_Bag;

final class Admin_Error_Bag_Mapper {

    /**
     * Best-effort mapping from validator-shaped objects that expose:
     *  - $validator->err_flds (array of field keys)
     *  - $validator->err_msgs (array of messages by index)
     *
     * @param object|null $validator
     */
    public static function from_validator( ?object $validator ): Error_Bag {
        if ( ! $validator ) {
            return new Error_Bag();
        }

        $fields = $validator->err_flds ?? array();
        $msgs   = $validator->err_msgs ?? array();

        if ( ! is_array( $fields ) || ! is_array( $msgs ) ) {
            return new Error_Bag();
        }

        $map = array();
        foreach ( $fields as $idx => $field_key ) {
            $key = strval( $field_key );
            $msg = array_key_exists( $idx, $msgs ) ? strval( $msgs[ $idx ] ) : '';

            if ( '' !== $key && '' !== $msg ) {
                $map[ $key ] = $msg;
            }
        }

        return new Error_Bag( $map );
    }
}
