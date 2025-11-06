<?php
/**
 * Matches administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
/** @var object $league */
/** @var string $season */
/** @var array  $teams */
/** @var int    $team_id */
/** @var string $group */
/** @var string $match_day */
/** @var array  $matches */
?>
<div class="row gx-3 justify-content-between mb-3">
    <div class="col-auto mb-3 mb-md-0">
        <form id="matches-filter" method="get" class="form-control">
            <input type="hidden" name="page" value="racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s" />
            <input type="hidden" name="view" value="league" />
            <input type="hidden" name="league_id" value="<?php echo esc_attr( $league->id ); ?>" />
            <input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
            <div class="row gx-3 align-items-center">
                <?php
                if ( ! empty( $league->current_season['num_match_days'] ) ) {
                    ?>
                    <div class="col-12 col-md-auto mb-3 mb-md-0">
                        <label>
                            <select size='1' name='match_day'>
                                <option value="-1"><?php esc_html_e( 'Show all Matches', 'racketmanager' ); ?></option>
                                <?php
                                for ( $racketmanager_i = 1; $racketmanager_i <= $league->current_season['num_match_days']; $racketmanager_i++ ) {
                                    ?>
                                    <option value='<?php echo esc_html( $racketmanager_i ); ?>'<?php selected( $league->match_day, $racketmanager_i ); ?>>
                                        <?php
                                        /* translators: %d: match day */
                                        echo esc_html( sprintf( __( 'Match Day %d', 'racketmanager' ), $racketmanager_i ) );
                                        ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </label>
                    </div>
                    <?php
                }
                ?>
                <div class="col-auto">
                    <label>
                        <select size="1" name="team_id">
                            <option value=""><?php esc_html_e( 'Choose Team', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $teams as $racketmanager_team ) {
                                ?>
                                <option value="<?php echo esc_html( $racketmanager_team->id ); ?>"<?php echo selected( $racketmanager_team->id, $team_id ); ?>><?php echo esc_html( $racketmanager_team->title ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-auto">
        <?php
        if ( ! empty( $league->current_season['num_match_days'] ) ) {
            ?>
            <!-- Bulk Editing of Matches -->
            <form action="/admin.php" method="get" class="form-control">
                <input type="hidden" name="page" value="racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s" />
                <input type="hidden" name="view" value="match" />
                <input type="hidden" name="league_id" value="<?php echo esc_attr( $league->id ); ?>" />
                <input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />
                <input type="hidden" name="group" value="<?php echo esc_html( $group ); ?>" />
                <div class="row gx-3 align-items-center">
                    <div class="col-auto">
                        <label>
                            <select size="1" name="match_day">
                                <?php
                                for ( $racketmanager_i = 1; $racketmanager_i <= $league->current_season['num_match_days']; $racketmanager_i++ ) {
                                    ?>
                                    <option value='<?php echo esc_html( $racketmanager_i ); ?>'<?php selected( $league->match_day, $racketmanager_i ); ?>>
                                        <?php
                                        /* translators: %d: match day */
                                        echo esc_html( sprintf( __( '%d. Match Day', 'racketmanager' ), $racketmanager_i ) );
                                        ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </label>
                    </div>
                    <div class="col-auto">
                        <input type="hidden" name="league-tab" value="matches" />
                        <button type="submit" class="btn btn-secondary action"><?php esc_html_e( 'Edit Matches', 'racketmanager' ); ?></button>
                    </div>
                </div>
            </form>
            <?php
        }
        ?>
    </div>
</div>

<form id="matches-action" action="" method="post">
    <?php wp_nonce_field( 'racketmanager_matches-bulk', 'racketmanager_nonce' ); ?>
    <input type="hidden" name="current_match_day" value="<?php echo esc_html( $match_day ); ?>" />
    <input type="hidden" name="league-tab" value="matches" />
    <input type="hidden" name="group" value="<?php echo esc_html( $group ); ?>" />
    <input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
    <div class="row gx-3 mb-3 align-items-center">
        <div class="col-auto">
            <label>
                <select name="delMatchOption" size="1">
                    <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                </select>
            </label>
        </div>
        <div class="col-auto">
            <input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="delMatches" id="delMatches" class="btn btn-secondary action" />
        </div>
    </div>

    <table class="table table-striped table-borderless" title="<?php esc_html_e( 'Match Plan', 'racketmanager' ); ?>" aria-label="<?php esc_html_e( 'matches', 'racketmanager' ); ?>">
        <thead>
            <tr>
                <th scope="col" class="check-column">
                    <label for="check-all-matches"></label><input type="checkbox" id="check-all-matches" onclick="Racketmanager.checkAll(document.getElementById('matches-action'));" />
                </th>
                <th scope="col"><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                <?php
                if ( ! empty( $league->groups ) && $league->event->competition->is_championship ) {
                    ?>
                    <th scope="col" class="column-num"><?php esc_html_e( 'Group', 'racketmanager' ); ?></th>
                    <?php
                }
                ?>
                <th scope="col" class="match-title"><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Location', 'racketmanager' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Begin', 'racketmanager' ); ?></th>
                <?php
                if ( isset( $league->num_rubbers ) && $league->num_rubbers > 0 ) {
                    ?>
                    <th scope="col"><?php echo esc_html__( 'Rubbers', 'racketmanager' ); ?></th>
                    <?php
                } else {
                    ?>
                    <th scope="col" colspan="<?php echo esc_html( $league->num_sets ); ?>" style="text-align: center;"><?php echo esc_html__( 'Sets', 'racketmanager' ); ?></th>
                    <?php
                }
                ?>
                <th scope="col" class="score"><?php esc_html_e( 'Score', 'racketmanager' ); ?></th>
            </tr>
        </thead>
        <tbody id="the-list-matches-<?php echo esc_html( $group ); ?>" class="lm-form-table">
            <?php
            if ( $matches ) {
                $racketmanager_class = '';
                ?>
                <?php
                foreach ( $matches as $match ) {
                    $racketmanager_class = ( 'alternate' === $racketmanager_class ) ? '' : 'alternate';
                    ?>
                    <tr class="<?php echo esc_html( $racketmanager_class ); ?>">
                        <th scope="row" class="check-column">
                            <input type="hidden" name="matches[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->id ); ?>" />
                            <input type="hidden" name="home_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->home_team ); ?>" />
                            <input type="hidden" name="away_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->away_team ); ?>" />
                            <label for="match-<?php echo esc_html( $match->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $match->id ); ?>" name="match[<?php echo esc_html( $match->id ); ?>]" id="match-<?php echo esc_html( $match->id ); ?>" />
                        </th>
                        <td><?php echo esc_html( $match->id ); ?></td>
                        <td><?php echo esc_html( (str_starts_with($match->date, '0000-00-00')) ? 'N/A' : mysql2date( $racketmanager->date_format, $match->date ) ); ?></td>
                        <?php
                        if ( ! empty( $league->groups ) && $league->event->competition->is_championship ) {
                            ?>
                            <td class="column-num"><?php echo esc_html( $match->group ); ?></td>
                            <?php
                        }
                        ?>
                        <td class="match-title"><a href="/wp-admin/admin.php?page=racketmanager&amp;subpage=match&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;edit=<?php echo esc_html( $match->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>
                            <?php
                            if ( isset( $group ) ) {
                                echo esc_html( '&amp;group=' . $group );
                            }
                            ?>
                        "><?php echo esc_html( $match->match_title ); ?></a></td>
                        <td><?php echo esc_html( ( empty( $match->location ) ) ? 'N/A' : $match->location ); ?></td>
                        <td><?php echo esc_html( ( '00:00' === $match->hour . ':' . $match->minutes ) ? 'N/A' : mysql2date( $racketmanager->time_format, $match->date ) ); ?></td>
                        <?php
                        if ( ! empty( $league->num_rubbers ) ) {
                            if ( is_numeric( $match->home_team ) && is_numeric( $match->away_team ) ) {
                                ?>
                                <td><a class="btn btn-secondary" href="<?php echo esc_html( $match->link ) . 'result/'; ?>"><?php echo esc_html__( 'View Rubbers', 'racketmanager' ); ?></a></td>
                                <?php
                            }
                        } else {
                            for ( $i = 1; $i <= $league->num_sets; $i++ ) {
                                if ( ! isset( $match->sets[ $i ] ) ) {
                                    $match->sets[ $i ] = array(
                                        'player1'  => '',
                                        'player2'  => '',
                                        'tiebreak' => '',
                                    );
                                }
                                ?>
                                <td>
                                    <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player1" class="visually-hidden"><?php esc_html_e( 'Set player 1', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player1" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player1]" value="<?php echo esc_html( $match->sets[ $i ]['player1'] ); ?>" />
                                    &nbsp;:&nbsp;
                                    <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player2" class="visually-hidden"><?php esc_html_e( 'Set player 2', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player2" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player2]" value="<?php echo esc_html( $match->sets[ $i ]['player2'] ); ?>" />
                                    <br>
                                    <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" class="visually-hidden"><?php esc_html_e( 'Tie break', 'racketmanager' ); ?></label><input class="points tie-break" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo esc_html( $match->sets[ $i ]['tiebreak'] ); ?>" />
                                </td>
                                <?php
                            }
                        }
                        ?>
                        <td class="score">
                            <label for="home_points-<?php echo esc_html( $match->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Home points', 'racketmanager' ); ?></label><input class="points" type="text" size="2" style="text-align: center;" id="home_points-<?php echo esc_html( $match->id ); ?>" name="home_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( isset( $match->home_points ) ? sprintf( '%g', $match->home_points ) : '' ); ?>" />
                            &nbsp;:&nbsp;
                            <label for="away_points-<?php echo esc_html( $match->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Away points', 'racketmanager' ); ?></label><input class="points" type="text" size="2" style="text-align: center;" id="away_points-<?php echo esc_html( $match->id ); ?>" name="away_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( isset( $match->away_points ) ? sprintf( '%g', $match->away_points ) : '' ); ?>" />
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <?php
            }
            ?>
        </tbody>
    </table>

    <?php do_action( 'racketmanager_match_administration_descriptions' ); ?>

    <div class="">
        <?php
        if ( ! $league->event->competition->is_championship && $league->get_page_links( 'matches' ) ) {
            ?>
            <div class=""><?php echo esc_html( $league->get_page_links( 'matches' ) ); ?></div>
            <?php
        }
        ?>

        <?php
        if ( $matches ) {
            ?>
            <input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
            <input type="hidden" name="num_rubbers" value="<?php echo esc_html( $league->num_rubbers ); ?>" />
            <input type="hidden" name="updateLeague" value="results" />
            <input type="submit" name="updateResults" value="<?php esc_html_e( 'Update Results', 'racketmanager' ); ?>" class="btn btn-primary" />
            <?php
        }
        ?>
    </div>
</form>
