<?php
/**
 * Competition Settings events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $competition */
/** @var int    $competition_id */
/** @var string $add_link */
$events   = $competition->get_events();
?>
<div class="mb-3 form-control">
    <form id='events-action' method='post' action='' class='form-control mb-3'>
        <?php wp_nonce_field( 'racketmanager_events-bulk', 'racketmanager_event_nonce' ); ?>
        <input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
        <div class="row gx-3 mb-3 align-items-center">
            <!-- Bulk Actions -->
            <div class="col-auto">
                <label>
                    <select class="form-select" name="action">
                        <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                    </select>
                </label>
            </div>
            <div class="col-auto">
                <button name="doActionEvent" id="doActionEvent" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
            </div>
        </div>
        <table class="table table-striped table-borderless">
            <thead class="table-dark">
                <tr>
                    <th class="check-column"><input type="checkbox" id="event-select-all" onclick="Racketmanager.checkAll(document.getElementById('events-action'));" /><label for="event-select-all" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label></th>
                    <th class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                    <th class=""><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
                    <th class=""><?php esc_html_e( 'Age', 'racketmanager' ); ?></th>
                </tr>
            </thead>
            <?php
            if ( $events ) {
                ?>
                <tbody>
                <?php
                foreach( $events as $event ) {
                    ?>
                    <tr>
                        <td class="check-column"><input type="checkbox" id="event-select-<?php echo esc_attr( $event->id ); ?>" value="<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" /><label for="event-select-<?php echo esc_attr( $event->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label></td>
                        <td class=""><a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=event&amp;event_id=<?php echo esc_html( $event->id ); ?>&competition_id=<?php echo esc_attr( $competition->id ); ?><?php echo esc_attr( $add_link ); ?>"><?php echo esc_html( $event->name ); ?></a></td>
                        <td class=""><?php echo esc_html( Util_Lookup::get_event_type( $event->type ) ); ?></td>
                        <td class=""><?php echo esc_html( Util_Lookup::get_age_limit( $event->age_limit ) ); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                <?php
            }
            ?>
        </table>
    </form>
</div>
<div class="mb-3">
    <!-- Add New Event -->
    <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&amp;view=event-config&competition_id=<?php echo esc_attr( $competition->id ); ?><?php echo esc_attr( $add_link ); ?>" class="btn btn-primary submit"><?php esc_html_e( 'Add Event', 'racketmanager' ); ?></a>
</div>
