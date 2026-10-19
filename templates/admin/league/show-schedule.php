<?php
/**
 * League Schedule administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Event_View_Model;

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Admin_League_Schedule_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $competition    = $vm->competition;
    $season         = $vm->season;
    $competition_id = $vm->competition_id;
    $events         = $vm->events;
}

/** @var object $competition */
/** @var string $season */
/** @var int $competition_id */
/** @var array $events */
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s"><?php echo esc_html( ucfirst( $competition->get_type() ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <?php esc_html_e( 'Schedule', 'racketmanager' ); ?>
        </div>
    </div>
    <div class="row justify-content-between">
        <div class="col-auto">
            <h1><?php echo esc_html__( 'Schedule', 'racketmanager' ) . ' - ' . esc_html( $competition->name ) . ' ' . esc_html( $season ); ?></h1>
        </div>
    </div>
    <div class="alert_rm" id="alert-season" style="display:none;">
        <div class="alert__body">
            <div class="alert__body-inner" id="alert-season-response">
            </div>
        </div>
    </div>

    <?php if ( isset( $this ) && method_exists( $this, 'show_message' ) ) { $this->show_message(); } ?>
    <div id="">
        <form id='schedule-filter' method='post' action='' class='form-control mb-3'>
            <?php wp_nonce_field( 'racketmanager_schedule-matches', 'racketmanager_nonce' ); ?>

            <input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
            <div class="row gx-3 mb-2 align-items-center">
                <!-- Bulk Actions -->
                <div class="col-auto">
                    <label>
                        <select class="form-select" name="actionSchedule">
                            <option value="-1" disabled selected><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                            <option value="schedule"><?php esc_html_e( 'Schedule matches', 'racketmanager' ); ?></option>
                            <option value="delete"><?php esc_html_e( 'Delete matches', 'racketmanager' ); ?></option>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button name="scheduleAction" id="scheduleAction" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="check-column"><label for="event-all" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="event-all" onclick="Racketmanager.checkAll(document.getElementById('schedule-filter'));" /></th>
                        <th class=""><?php esc_html_e( 'Event', 'racketmanager' ); ?></th>
                        <th class=""></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $events_list = $events ?? array();
                    if ( $events_list ) {
                        foreach ( $events_list as $event ) {
                            $event_id          = $event->id ?? 0;
                            $event_name        = $event->name ?? '';
                            $event_link        = $event->event_link ?? '';
                            $can_send_fixtures = ! empty( $event->can_send_fixtures );
                            ?>
                            <tr>
                                <td class="check-column"><label for="event-<?php echo esc_attr( $event_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $event_id ); ?>" id="event-<?php echo esc_attr( $event_id ); ?>" name="event[<?php echo esc_html( $event_id ); ?>]" onclick="Racketmanager.checkAll(document.getElementById('schedule-filter'));" /></td>
                                <td><a href="<?php echo esc_html( $event_link ); ?>"><?php echo esc_html( $event_name ); ?></a></td>
                                <td>
                                    <?php
                                    if ( $can_send_fixtures ) {
                                        ?>
                                        <button class="btn btn-secondary sendFixtures" data-event-id="<?php echo esc_html( $event_id ); ?>"><?php esc_html_e( 'Send fixtures', 'racketmanager' ); ?></button>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </form>
    </div>
</div>
<script type="text/javascript">
    const sendFixtures = document.querySelectorAll('.sendFixtures');
    sendFixtures.forEach(el => el.addEventListener('click', function (e) {
        let eventId = this.dataset.eventId;
        Racketmanager.sendFixtures(e, eventId)
    }));
</script>
