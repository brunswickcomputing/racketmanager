<?php
/**
 * Tournament Validation API: Tournament validator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Validate
 */

namespace Racketmanager\validator;

use function Racketmanager\get_player;

/**
 * Class to implement the Tournament Validator object
 */
final class Validator_Tournament extends Validator_Config {
    /**
     * Validate name
     *
     * @param string|null $name name.
     *
     * @return object $validation updated validation object.
     */
    public function name( ?string $name ): object {
        if ( ! $name ) {
            $this->error      = true;
            $this->err_flds[] = 'tournamentName';
            $this->err_msgs[] = __( 'Name must be specified', 'racketmanager' );
        }
        return $this;
    }
    /**
     * Validate information
     *
     * @param object|null $information information.
     *
     * @return object $validation updated validation object.
     */
    public function information( ?object $information ): object {
        return $this;
    }
    /**
     * Validate partner details
     *
     * @param int $partner partner.
     * @param string $field_ref field reference.
     * @param string|null $field_name field name.
     * @param object $event event object.
     * @param string $season season name.
     * @param int $player_id player id.
     * @param string $date_end end date of competition.
     * @return object $validation updated validation object.
     */
    public function partner( int $partner, string $field_ref, ?string $field_name, object $event, string $season, int $player_id, string $date_end ): object {
        if ( empty( $field_name ) ) {
            $err_flds = 'partner';
        } else {
            $err_flds = 'partner-' . $field_ref;
        }
        if ( empty( $partner ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner not selected', 'racketmanager' );
        } else {
            $partner_found = false;
            $partner_teams = $event->get_teams(
                array(
                    'player' => $partner,
                    'season' => $season,
                )
            );
            foreach ( $partner_teams as $partner_team ) {
                if ( ! in_array( $player_id, $partner_team->player_id, true ) ) {
                    $partner_found = true;
                }
            }
            if ( $partner_found ) {
                $this->error      = true;
                $this->err_flds[] = $err_flds;
                $this->err_msgs[] = __( 'Partner is in another team in this event', 'racketmanager' );
            } else {
                $this->validate_partner_age( $partner, $event, $err_flds, $date_end );
            }
        }
        return $this;
    }
    /**
     * Validate partner age
     *
     * @param int $partner_id partner id.
     * @param object $event event object.
     * @param string $err_flds error field.
     * @param string $date_end end date of competition.
     */
    private function validate_partner_age( int $partner_id, object $event, string $err_flds, string $date_end ): void {
        if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
            return;
        }
        $partner = get_player( $partner_id );
        if ( ! $partner ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner not found', 'racketmanager' );
        }
        $partner_age = substr( $date_end, 0, 4 ) - intval( $partner->year_of_birth );
        if ( empty( $partner->age ) ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner has no age specified', 'racketmanager' );
        } elseif ( $event->age_limit >= 30 ) {
            if ( 'F' === $partner->gender && ! empty( $event->age_offset ) ) {
                $age_limit = $event->age_limit - $event->age_offset;
            } else {
                $age_limit = $event->age_limit;
            }
            if ( $partner_age < $age_limit ) {
                $this->error      = true;
                $this->err_flds[] = $err_flds;
                $this->err_msgs[] = __( 'Partner is too young', 'racketmanager' );
            }
        } elseif ( $partner_age > $event->age_limit ) {
            $this->error      = true;
            $this->err_flds[] = $err_flds;
            $this->err_msgs[] = __( 'Partner is too old', 'racketmanager' );
        }
    }
}
