<?php
/**
 * League Schedule administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Event_View_Model;

/** @var Admin_League_Schedule_Page_View_Model $vm */
$competition     = $vm->competition;
$season          = $vm->season;
$competition_id  = $vm->competition_id;
$events          = $vm->events;
$schedule_report = $vm->schedule_report;
$schedule_report      = isset( $schedule_report ) && is_array( $schedule_report ) ? $schedule_report : array();
$report_conflicts     = is_array( $schedule_report['conflicts'] ?? null ) ? $schedule_report['conflicts'] : array();
$report_alerts        = is_array( $schedule_report['alerts'] ?? null ) ? $schedule_report['alerts'] : array();
$report_pairings      = is_array( $schedule_report['resource_pairings'] ?? null ) ? $schedule_report['resource_pairings'] : array();
$has_schedule_report  = $report_conflicts || $report_alerts || $report_pairings;
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
    <?php if ( $has_schedule_report ) { ?>
        <div class="schedule-report mb-3" id="schedule-report">
            <details <?php echo $report_conflicts ? 'open' : ''; ?>>
                <summary class="fw-bold">
                    <?php
                    printf(
                        /* translators: 1: number of scheduling attempts 2: number of fixtures created */
                        esc_html__( 'Scheduling report - %1$d attempts, %2$d fixtures created', 'racketmanager' ),
                        (int) ( $schedule_report['retry'] ?? 0 ),
                        (int) ( $schedule_report['scheduled'] ?? 0 )
                    );
                    ?>
                </summary>
                <?php if ( $report_conflicts ) { ?>
                    <h2 class="h5 mt-3"><?php esc_html_e( 'Court conflicts', 'racketmanager' ); ?></h2>
                    <p><?php esc_html_e( 'The following teams share a court and are both at home in the same week. Adjust the match times or days and schedule again.', 'racketmanager' ); ?></p>
                    <table class="table table-sm table-striped schedule-report__conflicts">
                        <thead class="table-dark">
                            <tr>
                                <th><?php esc_html_e( 'Week', 'racketmanager' ); ?></th>
                                <th><?php esc_html_e( 'Division', 'racketmanager' ); ?></th>
                                <th><?php esc_html_e( 'Home team', 'racketmanager' ); ?></th>
                                <th><?php esc_html_e( 'Clashes with', 'racketmanager' ); ?></th>
                                <th><?php esc_html_e( 'Court', 'racketmanager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $report_conflicts as $conflict ) { ?>
                                <tr>
                                    <td><?php echo esc_html( (string) ( $conflict['week'] ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( (string) ( $conflict['division'] ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( (string) ( $conflict['home'] ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( (string) ( $conflict['with'] ?? '' ) ); ?></td>
                                    <td><?php echo esc_html( (string) ( $conflict['court'] ?? '' ) ); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                <?php if ( $report_alerts ) { ?>
                    <h2 class="h5 mt-3"><?php esc_html_e( 'Home and away equity', 'racketmanager' ); ?></h2>
                    <ul class="schedule-report__alerts">
                        <?php foreach ( $report_alerts as $alert ) { ?>
                            <li>
                                <?php
                                printf(
                                    /* translators: 1: team name 2: division name 3: number of home matches 4: ideal number of home matches */
                                    esc_html__( '%1$s (%2$s) has %3$d home matches (ideal %4$s)', 'racketmanager' ),
                                    esc_html( (string) ( $alert['team_name'] ?? '' ) ),
                                    esc_html( (string) ( $alert['division'] ?? '' ) ),
                                    (int) ( $alert['count'] ?? 0 ),
                                    esc_html( (string) ( $alert['ideal'] ?? '' ) )
                                );
                                ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
                <?php if ( $report_pairings ) { ?>
                    <h2 class="h5 mt-3"><?php esc_html_e( 'Court sharing', 'racketmanager' ); ?></h2>
                    <table class="table table-sm table-striped schedule-report__pairings">
                        <thead class="table-dark">
                            <tr>
                                <th><?php esc_html_e( 'Court', 'racketmanager' ); ?></th>
                                <th><?php esc_html_e( 'Teams', 'racketmanager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $report_pairings as $resource => $pairing_teams ) { ?>
                                <tr>
                                    <td><?php echo esc_html( (string) $resource ); ?></td>
                                    <td>
                                        <?php
                                        $pairing_labels = array();
                                        foreach ( (array) $pairing_teams as $pairing_team ) {
                                            $pairing_team     = (array) $pairing_team;
                                            $pairing_labels[] = esc_html( (string) ( $pairing_team['name'] ?? '' ) ) . ' <span class="text-muted">(' . esc_html( (string) ( $pairing_team['division'] ?? '' ) ) . ')</span>';
                                        }
                                        echo implode( ', ', $pairing_labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each label is escaped above.
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </details>
        </div>
    <?php } ?>
    <div id="">
        <form id='schedule-filter' method='post' action='' class='form-control mb-3'>
            <?php wp_nonce_field( 'racketmanager_schedule-matches', 'racketmanager_nonce' ); ?>

            <input type="hidden" name="competition_id" value="<?php echo esc_html( $competition_id ); ?>" />
            <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
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
                        <th class="check-column"><label for="event-all" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="event-all" data-action="check-all" data-target="schedule-filter" /></th>
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
                                <td class="check-column"><label for="event-<?php echo esc_attr( $event_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $event_id ); ?>" id="event-<?php echo esc_attr( $event_id ); ?>" name="event[<?php echo esc_html( $event_id ); ?>]" data-action="check-all" data-target="schedule-filter" /></td>
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
