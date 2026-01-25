<?php
/**
 * Cup planner administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array $final_matches */
/** @var object $competition */
/** @var object $cup_season */
/** @var string $tab */
/** @var string $season */
/** @var array $order_of_play */
/** @var object $validator */
$num_matches = count( $final_matches );
if ( empty( $num_events ) ) {
    $num_courts    = 1;
    $max_schedules = 0;
} else {
    $num_courts    = $num_events;
    $max_schedules = ceil( $num_matches / $num_courts ) + 1;
}
if ( '01:00:00' === $cup_season->time_increment ) {
    $max_schedules = $max_schedules * 2;
}
$column_width = floor( 12 / $num_courts );
$match_length = empty( $cup_season->time_increment ) ? 0 : strtotime( $cup_season->time_increment );
if ( ! is_array( $cup_season->orderofplay ) || count( $cup_season->orderofplay ) !== intval( $cup_season->num_courts ) ) {
    for ( $i = 0; $i < $cup_season->num_courts; $i++ ) {
        $order_of_play[ $i ]['court']      = 'Court ' . ( $i + 1 );
        $order_of_play[ $i ]['start_time'] = $cup_season->starttime;
        $order_of_play[ $i ]['matches']    = array();
    }
} else {
    $order_of_play = $cup_season->orderofplay;
}
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <?php esc_html_e( 'Plan', 'racketmanager' ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $competition->name ); ?> <?php echo esc_html( $season ); ?> - <?php esc_html_e( 'Plan', 'racketmanager' ); ?></h1>
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th scope="row" class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></th>
                        <td class="col-6"><?php echo esc_html( $cup_season->venue_name ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" class="col-6 col-md-3"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $cup_season->date_end ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" class="col-6 col-md-3"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( count( $final_matches ) ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <ul class="nav nav-pills">
        <li class="nav-item">
            <button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab" aria-controls="config" aria-selected="true"><?php esc_html_e( 'Config', 'racketmanager' ); ?></button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade" id="config" role="tabpanel" aria-labelledby="config-tab">
            <form id="cupDetails" class="form-control" method="POST">
                <?php wp_nonce_field( 'racketmanager_cup', 'racketmanager_cup-nonce' ); ?>
                <input type="hidden" name="competition_id" value=<?php echo esc_html( $competition->id ); ?> />
                <input type="hidden" name="season" value=<?php echo esc_attr( $season ); ?> />
                <div class="row g-3">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'startTime', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'startTime', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="startTime" id="startTime" value="<?php echo esc_html( $cup_season->starttime ); ?>" size="20" />
                            <label for="startTime"><?php esc_html_e( 'Start Time', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'timeIncrement', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'timeIncrement', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="timeIncrement" id="timeIncrement" value="<?php echo esc_html( $cup_season->time_increment ); ?>" size="20" />
                            <label for="timeIncrement"><?php esc_html_e( 'Time Increment', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
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
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'numCourtsAvailable', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'numCourtsAvailable', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="numCourtsAvailable" id="numCourtsAvailable" value="<?php echo esc_html( $cup_season->num_courts ); ?>" />
                            <label for="numCourtsAvailable"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" name="saveCup" class="btn btn-primary"><?php esc_html_e( 'Save cup', 'racketmanager' ); ?></button>
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
            <h2><?php esc_html_e( 'Final matches', 'racketmanager' ); ?></h2>
            <div class="col-2 col-sm-1"></div>
            <div class="col-10 col-sm-11">
                <div class="row text-center">
                    <?php
                    foreach ( $final_matches as $match ) {
                        if ( ! is_numeric( $match->home_team ) || ! is_numeric( $match->away_team ) ) {
                            $btn_type = 'warning';
                        } else {
                            $btn_type = 'success';
                        }
                        if ( is_numeric( $match->home_team ) ) {
                            $home_match_title = $match->teams['home']->title;
                        } else {
                            $home_match_title = $match->prev_home_match->match_title;
                        }
                        if ( is_numeric( $match->away_team ) ) {
                            $away_match_title = $match->teams['away']->title;
                        } else {
                            $away_match_title = $match->prev_away_match->match_title;
                        }
                        ?>
                        <div class="col-3 mb-3">
                            <div class="btn btn-<?php echo esc_attr( $btn_type ); ?> finals-match" name="match-<?php echo esc_html( $match->id ); ?>" id="match-<?php echo esc_html( $match->id ); ?>" draggable="true">
                                <div class="fw-bold">
                                    <?php echo esc_html( $match->league->title ); ?>
                                </div>
                                <div <?php echo is_numeric( $match->home_team ) ? null : 'class="fst-italic"'; ?>>
                                    <?php echo esc_html( $home_match_title ); ?>
                                </div>
                                <?php
                                if ( is_numeric( $match->home_team ) ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $match->teams['home']->club->shortcode ); ?>)</div>
                                    <?php
                                }
                                ?>
                                <div>
                                    <?php esc_html_e( 'vs', 'racketmanager' ); ?>
                                </div>
                                <div <?php echo is_numeric( $match->away_team ) ? null : 'class="fst-italic"'; ?>>
                                    <?php echo esc_html( $away_match_title ); ?>
                                </div>
                                <?php
                                if ( is_numeric( $match->away_team ) ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $match->teams['away']->club->shortcode ); ?>)</div>
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
                <form id="cup-planner" method="post" action="">
                    <?php wp_nonce_field( 'racketmanager_cup-planner', 'racketmanager_cup-planner-nonce' ); ?>
                    <input type="hidden" name="numFinals" value=<?php echo esc_attr( $num_matches ); ?> />
                    <input type="hidden" name="numCourts" value=<?php echo esc_attr( $cup_season->num_courts ); ?> />
                    <input type="hidden" name="startTime" value=<?php echo esc_attr( $cup_season->starttime ); ?> />
                    <input type="hidden" name="competition_id" value=<?php echo esc_attr( $competition->id ); ?> />
                    <input type="hidden" name="season" value=<?php echo esc_attr( $season ); ?> />
                    <div class="row text-center mb-3">
                        <div class="col-2 col-sm-1"><?php esc_html_e( 'Time', 'racketmanager' ); ?></div>
                        <div class="col-10 col-sm-11">
                            <div class="row">
                                <?php
                                for ( $i = 0; $i < $cup_season->num_courts; $i++ ) {
                                    ?>
                                    <div class="col-<?php echo esc_html( $column_width ); ?>">
                                        <div class="form-group mb-2">
                                            <label for="court-<?php echo esc_html( $i ); ?>"></label><input type="text" class="form-control" name="court[<?php echo esc_html( $i ); ?>]" id="court-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $order_of_play[ $i ]['court'] ); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label for="startTime-<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Start time', 'racketmanager' ); ?></label><input type="time" class="form-control" name="startTime[<?php echo esc_html( $i ); ?>]" id="startTime-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $order_of_play[ $i ]['start_time'] ); ?>" />
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
                        $start_time  = empty( $cup_season->starttime ) ? 0 : strtotime( $cup_season->starttime );
                        $time_offset = 0;
                        for ( $i = 0; $i < $max_schedules; $i++ ) {
                            $scheduled_players = array();
                            $player_warnings   = array();
                            ?>
                            <div class="row align-items-center text-center mb-3">
                                <div class="col-2 col-sm-1">
                                    <?php echo esc_html( gmdate( 'H:i', $start_time ) ); ?>
                                </div>
                                <div class="col-10 col-sm-11">
                                    <div class="row">
                                        <?php
                                        for ( $c = 0; $c < $cup_season->num_courts; $c++ ) {
                                            if ( isset( $order_of_play[ $c ]['matches'][ $i ] ) ) {
                                                $match_players = array();
                                                $match_id      = intval( $order_of_play[ $c ]['matches'][ $i ] );
                                                $match         = get_match( $match_id );
                                                if ( $match ) {
                                                    $match_players = match_add_players( $match_players, $match );
                                                    if ( ! empty( $match->prev_home_match ) ) {
                                                        $prev_match = get_match( $match->prev_home_match->id );
                                                        if ( $prev_match ) {
                                                            $match_players = match_add_players( $match_players, $prev_match );
                                                        }
                                                    }
                                                    if ( ! empty( $match->prev_away_match ) ) {
                                                        $prev_match = get_match( $match->prev_away_match->id );
                                                        if ( $prev_match ) {
                                                            $match_players = match_add_players( $match_players, $prev_match );
                                                        }
                                                    }
                                                    foreach ( $match_players as $player_id ) {
                                                        $player_found = in_array($player_id, $scheduled_players, true);
                                                        if ( false !== $player_found ) {
                                                            $player = get_player( $player_id );
                                                            if ( $player ) {
                                                                $player_warnings[] = $player->get_fullname();
                                                            }
                                                        }
                                                        $scheduled_players[] = $player_id;
                                                    }
                                                }
                                            } else {
                                                $match_id = null;
                                            }
                                            ?>
                                            <div class="col-<?php echo esc_html( $column_width ); ?> tournament-match" name="schedule[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="schedule-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>">
                                                <input type="hidden" class="matchId" name="match[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="match-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $match_id ); ?>" />
                                                <input type="hidden" class="" name="matchtime[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="matchtime-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $time_offset ); ?>" />
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if ( $player_warnings ) {
                                    ?>
                                    <div class="mb-3 mt-3">
                                        <span class="fw-bold"><?php esc_html_e( 'Potential clashes', 'racketmanager' ); ?></span>
                                        <?php
                                        foreach ( $player_warnings as $player_warning ) {
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
                            $start_time  = $start_time + $match_length;
                            $time_offset = $time_offset + $match_length;
                        }
                        ?>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary" name="savePlan" id="savePlan"><?php esc_html_e( 'Save schedule', 'racketmanager' ); ?></button>
                        <button class="btn btn-secondary" name="resetPlan" id="resetPlan"><?php esc_html_e( 'Reset schedule', 'racketmanager' ); ?></button>
                    </div>
                </form>
                <?php
            }
            ?>
        </div>
</div>
<?php
wp_register_script( 'racketmanager-draggable', plugins_url( '/js/draggable.js', __DIR__ ), array(), RACKETMANAGER_VERSION, true );
wp_enqueue_script( 'racketmanager-draggable' );
?>
