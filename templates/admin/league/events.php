<?php
/**
 *
 * League events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $competition */
/** @var string $season */
/** @var array $competition_events */
?>
    <div class="row">
        <div class="col-12">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Event', 'racketmanager' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'racketmanager' ); ?></th>
                        <th><?php esc_html_e( 'Age', 'racketmanager' ); ?></th>
                        <th class="text-end"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></th>
                        <th class="text-end"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></th>
                        <th class="text-end"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></th>
                        <th class="text-end"><?php esc_html_e( 'Players', 'racketmanager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ( $competition_events as $event ) {
                        $age_limit      = empty( $event->age_limit ) ? 0 : $event->age_limit;
                        $age_offset     = null;
                        if ( is_numeric( $age_limit ) ) {
                            if ( isset( $event->age_offset ) ) {
                                $age_offset = is_numeric( $event->age_offset ) ? $age_limit - intval( $event->age_offset ) : null;
                            }
                            if ( empty( $age_offset ) ) {
                                $age_offset = null;
                            } else {
                                $age_offset = '(' . $age_offset . ')';
                            }
                        }
                        ?>
                        <tr>
                            <td><a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&view=event&competition_id=<?php echo esc_attr( $competition->id ); ?>&event_id=<?php echo esc_attr( $event->event_id ); ?>&season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $event->event_name ); ?></a></td>
                            <td><?php echo esc_html( Util_Lookup::get_event_type( $event->format ) ); ?></td>
                            <td><?php echo esc_html( Util_Lookup::get_age_limit( $age_limit ) ) . esc_html( $age_offset ); ?></td>
                            <td class="text-end"><?php echo esc_html( $event->num_leagues ); ?></td>
                            <td class="text-end"><?php echo esc_html( $event->num_clubs ); ?></td>
                            <td class="text-end"><?php echo esc_html( $event->num_teams ); ?></td>
                            <td class="text-end"><?php echo esc_html( $event->num_players ); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
