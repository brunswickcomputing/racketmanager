<?php
/**
 * Tournaments planner administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Plan_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $final_matches = $vm->final_matches;
    $order_of_play = $vm->order_of_play;
    $tournament    = $vm->tournament;
    $tab           = $vm->tab;
    $errors        = $vm->errors;
}
if ( ! isset( $errors ) || ! ( $errors instanceof Error_Bag ) ) {
    $errors = new Error_Bag();
}

/** @var Fixture_Details_DTO[] $final_matches */
/** @var array $order_of_play */
/** @var object $tournament */
/** @var string $tab */
$num_matches = count( $final_matches );
if ( 0 === intval( $tournament->get_num_courts() ) ) {
    $num_courts    = 1;
    $max_schedules = 0;
} else {
    $num_courts    = $tournament->get_num_courts();
    $max_schedules = ceil( $num_matches / $num_courts ) + 1;
}
if ( '01:00:00' === $tournament->get_time_increment() ) {
    $max_schedules = $max_schedules * 2;
}
$column_width = floor( 12 / $num_courts );
$fixture_length = empty( $tournament->get_time_increment() ) ? null : strtotime( $tournament->get_time_increment() );
if ( ! is_array( $tournament->get_order_of_play() ) || count( $tournament->get_order_of_play() ) !== intval( $tournament->get_num_courts() ) ) {
    for ( $i = 0; $i < $tournament->get_num_courts(); $i++ ) {
        $order_of_play[ $i ]['court']      = 'Court ' . ( $i + 1 );
        $order_of_play[ $i ]['start_time'] = $tournament->get_start_time();
        $order_of_play[ $i ]['matches']    = array();
    }
} else {
    $order_of_play = $tournament->get_order_of_play();
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
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></th>
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
            <form id="tournamentDetails" class="form-control" method="POST">
                <?php wp_nonce_field( 'racketmanager_tournament-finals-config', 'racketmanager_nonce' ); ?>
                <input type="hidden" name="tournamentId" value=<?php echo esc_html( $tournament->get_id() ); ?> />
                <div class="row g-3">
                    <div class="col">
                        <div class="form-floating mb-3">
                            <?php
                            $start_time = $_POST['startTime'] ?? $tournament->get_start_time();
                            $field_key  = 'start_time';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="startTime" id="startTime" value="<?php echo esc_html( $start_time ); ?>" />
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
                            $time_increment = $_POST['timeIncrement'] ?? $tournament->get_time_increment();
                            $field_key  = 'timeIncrement';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="timeIncrement" id="timeIncrement" value="<?php echo esc_html( $time_increment ); ?>" />
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
                            $num_courts = $_POST['numCourtsAvailable'] ?? $tournament->get_num_courts();
                            $field_key  = 'numCourtsAvailable';
                            $is_invalid = $errors->has( $field_key );
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="numCourtsAvailable" id="numCourtsAvailable" value="<?php echo esc_html( $num_courts ); ?>" />
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
        <div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
            <h2><?php esc_html_e( 'Final matches', 'racketmanager' ); ?></h2>
            <div class="col-2 col-sm-1"></div>
            <div class="col-10 col-sm-11">
                <div class="row text-center">
                    <?php
                    foreach ( $final_matches as $fixture_dto ) {
                        $fixture = $fixture_dto->fixture;
                        if ( ! is_numeric( $fixture->home_team ) || ! is_numeric( $fixture->away_team ) ) {
                            $btn_type = 'warning';
                        } else {
                            $btn_type = 'success';
                        }
                        if ( is_numeric( $fixture->home_team ) ) {
                            $home_match_title = $fixture_dto->home_team->team->get_name();
                        } else {
                            $home_match_title = $fixture_dto->prev_home_match_title ?? $fixture->home_team;
                        }
                        if ( is_numeric( $fixture->away_team ) ) {
                            $away_match_title = $fixture_dto->away_team->team->get_name();
                        } else {
                            $away_match_title = $fixture_dto->prev_away_match_title ?? $fixture->away_team;
                        }
                        ?>
                        <div class="col-3 mb-3">
                            <div class="btn btn-<?php echo esc_attr( $btn_type ); ?> finals-match" name="match-<?php echo esc_html( $fixture->id ); ?>" id="match-<?php echo esc_html( $fixture->id ); ?>" draggable="true">
                                <div class="fw-bold">
                                    <?php echo esc_html( $fixture_dto->league->title ); ?>
                                </div>
                                <div <?php echo is_numeric( $fixture->home_team ) ? null : 'class="fst-italic"'; ?>>
                                    <?php echo esc_html( $home_match_title ); ?>
                                </div>
                                <?php
                                if ( is_numeric( $fixture->home_team ) && isset( $fixture_dto->home_team->club ) ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $fixture_dto->home_team->club->shortcode ); ?>)</div>
                                    <?php
                                }
                                ?>
                                <div>
                                    <?php esc_html_e( 'vs', 'racketmanager' ); ?>
                                </div>
                                <div <?php echo is_numeric( $fixture->away_team ) ? null : 'class="fst-italic"'; ?>>
                                    <?php echo esc_html( $away_match_title ); ?>
                                </div>
                                <?php
                                if ( is_numeric( $fixture->away_team ) && isset( $fixture_dto->away_team->club ) ) {
                                    ?>
                                    <div class="fst-italic">(<?php echo esc_html( $fixture_dto->away_team->club->shortcode ); ?>)</div>
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
                    <input type="hidden" name="numFinals" value=<?php echo esc_html( $num_matches ); ?> />
                    <input type="hidden" name="numCourts" value=<?php echo esc_html( $tournament->get_num_courts() ); ?> />
                    <input type="hidden" name="startTime" value=<?php echo esc_html( $tournament->get_start_time() ); ?> />
                    <input type="hidden" name="tournamentId" value=<?php echo esc_html( $tournament->get_id() ); ?> />
                    <div class="row text-center mb-3">
                        <div class="col-2 col-sm-1"><?php esc_html_e( 'Time', 'racketmanager' ); ?></div>
                        <div class="col-10 col-sm-11">
                            <div class="row">
                                <?php
                                for ( $i = 0; $i < $tournament->get_num_courts(); $i++ ) {
                                    ?>
                                    <div class="col-<?php echo esc_html( $column_width ); ?>">
                                        <div class="form-group mb-2">
                                            <label for="court-<?php echo esc_html( $i ); ?>"></label><input type="text" class="form-control" name="court[<?php echo esc_html( $i ); ?>]" id="court-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $order_of_play[ $i ]['court'] ); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label for="courtStartTime-<?php echo esc_html( $i ); ?>" class="visually-hidden">
                                                <?php esc_html_e( 'Start time', 'racketmanager '); ?>
                                            </label>
                                            <input type="time" class="form-control" name="courtStartTime[<?php echo esc_html( $i ); ?>]" id="courtStartTime-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $order_of_play[ $i ]['start_time'] ); ?>" />
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
                        $start_time  = strtotime( $tournament->get_start_time() );
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
                                        for ( $c = 0; $c < $tournament->get_num_courts(); $c++ ) {
                                            if ( isset( $order_of_play[ $c ]['matches'][ $i ] ) ) {
                                                $fixtures_players = array();
                                                $fixture_id      = intval( $order_of_play[ $c ]['matches'][ $i ] );
                                                $fixture         = get_match( $fixture_id );
                                                if ( $fixture ) {
                                                    $fixtures_players = match_add_players( $fixtures_players, $fixture );
                                                    if ( ! empty( $fixture->prev_home_match ) ) {
                                                        $prev_match = get_match( $fixture->prev_home_match->id );
                                                        if ( $prev_match ) {
                                                            $fixtures_players = match_add_players( $fixtures_players, $prev_match );
                                                        }
                                                    }
                                                    if ( ! empty( $fixture->prev_away_match ) ) {
                                                        $prev_match = get_match( $fixture->prev_away_match->id );
                                                        if ( $prev_match ) {
                                                            $fixtures_players = match_add_players( $fixtures_players, $prev_match );
                                                        }
                                                    }
                                                    foreach ( $fixtures_players as $player_id ) {
                                                        $player_found = in_array( $player_id, $scheduled_players, true );
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
                                                $fixture_id = null;
                                            }
                                            ?>
                                            <div class="col-<?php echo esc_html( $column_width ); ?> tournament-match" name="schedule[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="schedule-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>">
                                                <input type="hidden" class="matchId" name="match[<?php echo esc_html( $c ); ?>][<?php echo esc_html( $i ); ?>]" id="match-<?php echo esc_html( $c ); ?>-<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $fixture_id ); ?>" />
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
                            $start_time  = $start_time + $fixture_length;
                            $time_offset = $time_offset + $fixture_length;
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
