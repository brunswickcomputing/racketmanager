<?php
/**
 * Tournaments planner administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;

/** @var Tournament_Plan_Page_View_Model $vm */
$tournament     = $vm->tournament;
$tab            = $vm->tab;
$errors         = $vm->errors;
$order_of_play  = $vm->order_of_play;
$column_width   = $vm->column_width;
$max_schedules  = $vm->max_schedules;
$schedule_slots = $vm->schedule_slots;
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->get_id() ); ?>&amp;season=<?php echo esc_attr( $tournament->get_season() ); ?>"><?php echo esc_html( $tournament->get_name() ); ?></a> &raquo; <?php esc_html_e( 'Tournament Planner', 'racketmanager' ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $tournament->get_name() ); ?> - <?php esc_html_e( 'Plan', 'racketmanager' ); ?></h1>
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></th>
                        <td class="col-6"><?php echo esc_html( $tournament->get_meta( 'venue_name' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->get_end_date() ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( count( $vm->final_matches ) ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <ul class="nav nav-pills">
        <li class="nav-item">
            <button class="nav-link" id="finals-tab" data-bs-toggle="tab" data-bs-target="#finals" type="button" role="tab" aria-controls="finals" aria-selected="true"><?php esc_html_e( 'Finals', 'racketmanager' ); ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab" aria-controls="config" aria-selected="true"><?php esc_html_e( 'Config', 'racketmanager' ); ?></button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade" id="config" role="tabpanel" aria-labelledby="config-tab">
            <form id="tournamentDetails" class="form-control" method="POST">
                <?php wp_nonce_field( 'racketmanager_tournament-finals-config', 'racketmanager_nonce' ); ?>
                <input type="hidden" name="tournamentId" value="<?php echo esc_attr( $tournament->get_id() ); ?>" />
                <div class="row g-3">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <?php
                            $field_key  = 'start_time';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="startTime" id="startTime" value="<?php echo esc_html( $vm->config_values['start_time'] ); ?>" />
                            <label for="startTime"><?php esc_html_e( 'Start Time', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( $field_key ) ) ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <?php
                            $field_key  = 'timeIncrement';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="timeIncrement" id="timeIncrement" value="<?php echo esc_html( $vm->config_values['time_increment'] ); ?>" />
                            <label for="timeIncrement"><?php esc_html_e( 'Time Increment', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( $field_key ) ) ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $field_key  = 'numCourtsAvailable';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="numCourtsAvailable" id="numCourtsAvailable" value="<?php echo esc_html( $vm->config_values['num_courts'] ); ?>" />
                            <label for="numCourtsAvailable"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( $field_key ) ) ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" name="saveTournamentFinalsConfig" class="btn btn-primary"><?php esc_html_e( 'Save tournament', 'racketmanager' ); ?></button>
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="finals" role="tabpanel" aria-labelledby="finals-tab">
            <h2><?php esc_html_e( 'Final fixtures', 'racketmanager' ); ?></h2>
            <div class="col-2 col-sm-1"></div>
            <div class="col-10 col-sm-11">
                <div class="row text-center">
                    <?php
                    foreach ( $vm->final_cards as $card ) {
                        ?>
                        <div class="col-3 mb-3">
                            <div class="btn btn-<?php echo esc_attr( $card->btn_type ); ?> finals-match" name="match-<?php echo esc_html( $card->id ); ?>" id="match-<?php echo esc_html( $card->id ); ?>" draggable="true">
                                <div class="fw-bold">
                                    <?php echo esc_html( $card->league_title ); ?>
                                </div>
                                <div <?php echo $card->is_home_pending ? 'class="fst-italic"' : null; ?>>
                                    <?php echo esc_html( $card->home_title ); ?>
                                </div>
                                <?php
                                if ( null !== $card->home_club ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $card->home_club ); ?>)</div>
                                    <?php
                                }
                                ?>
                                <div>
                                    <?php esc_html_e( 'vs', 'racketmanager' ); ?>
                                </div>
                                <div <?php echo $card->is_away_pending ? 'class="fst-italic"' : null; ?>>
                                    <?php echo esc_html( $card->away_title ); ?>
                                </div>
                                <?php
                                if ( null !== $card->away_club ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $card->away_club ); ?>)</div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
            if ( $max_schedules ) {
                ?>
                <h2><?php esc_html_e( 'Schedule', 'racketmanager' ); ?></h2>
                <form id="tournament-planner" method="post" action="">
                    <?php wp_nonce_field( 'racketmanager_tournament-planner', 'racketmanager_nonce' ); ?>
                    <input type="hidden" name="numFinals" value="<?php echo esc_attr( count( $vm->final_matches ) ); ?>" />
                    <input type="hidden" name="numCourts" value="<?php echo esc_attr( $tournament->get_num_courts() ); ?>" />
                    <input type="hidden" name="startTime" value="<?php echo esc_attr( $tournament->get_start_time() ); ?>" />
                    <input type="hidden" name="tournamentId" value="<?php echo esc_attr( $tournament->get_id() ); ?>" />
                    <div class="row text-center mb-3">
                        <div class="col-2 col-sm-1"><?php esc_html_e( 'Time', 'racketmanager' ); ?></div>
                        <div class="col-10 col-sm-11">
                            <div class="row">
                                <?php
                                foreach ( $order_of_play as $i => $court_plan ) {
                                    ?>
                                    <div class="col-<?php echo esc_html( $column_width ); ?>">
                                        <div class="form-group mb-2">
                                            <label for="court-<?php echo esc_html( $i ); ?>"></label><input type="text" class="form-control" name="court[<?php echo esc_html( $i ); ?>]" id="court-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $court_plan['court'] ); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label for="courtStartTime-<?php echo esc_html( $i ); ?>" class="visually-hidden">
                                                <?php esc_html_e( 'Start time', 'racketmanager' ); ?>
                                            </label>
                                            <input type="time" class="form-control" name="courtStartTime[<?php echo esc_html( $i ); ?>]" id="courtStartTime-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $court_plan['start_time'] ); ?>" />
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <?php
                        foreach ( $schedule_slots as $slot ) {
                            ?>
                            <div class="row align-items-center text-center mb-3">
                                <div class="col-2 col-sm-1">
                                    <?php echo esc_html( $slot->time_formatted ); ?>
                                </div>
                                <div class="col-10 col-sm-11">
                                    <div class="row">
                                        <?php
                                        foreach ( $slot->courts as $cell ) {
                                            ?>
                                            <div class="col-<?php echo esc_html( $column_width ); ?> tournament-match" name="schedule[<?php echo esc_html( $cell->court ); ?>][<?php echo esc_html( $cell->slot ); ?>]" id="schedule-<?php echo esc_html( $cell->court ); ?>-<?php echo esc_html( $cell->slot ); ?>">
                                                <input type="hidden" class="matchId" name="match[<?php echo esc_html( $cell->court ); ?>][<?php echo esc_html( $cell->slot ); ?>]" id="match-<?php echo esc_html( $cell->court ); ?>-<?php echo esc_html( $cell->slot ); ?>" value="<?php echo esc_html( $cell->fixture_id ); ?>" />
                                                <input type="hidden" class="" name="matchtime[<?php echo esc_html( $cell->court ); ?>][<?php echo esc_html( $cell->slot ); ?>]" id="matchtime-<?php echo esc_html( $cell->court ); ?>-<?php echo esc_html( $cell->slot ); ?>" value="<?php echo esc_html( $cell->time_offset ); ?>" />
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if ( $slot->player_warnings ) {
                                    ?>
                                    <div class="mb-3 mt-3">
                                        <span class="fw-bold"><?php esc_html_e( 'Potential clashes', 'racketmanager' ); ?></span>
                                        <?php
                                        foreach ( $slot->player_warnings as $player_warning ) {
                                            ?>
                                            <div class="">
                                                <span><?php echo esc_html( $player_warning ); ?></span>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary" name="saveTournamentPlan" id="saveTournamentPlan"><?php esc_html_e( 'Save schedule', 'racketmanager' ); ?></button>
                        <button class="btn btn-secondary" name="resetTournamentPlan" id="resetTournamentPlan"><?php esc_html_e( 'Reset schedule', 'racketmanager' ); ?></button>
                    </div>
                </form>
                <?php
            }
            ?>
        </div>
    </div>
</div>
