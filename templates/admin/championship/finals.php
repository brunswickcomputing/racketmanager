<?php
/**
 * Finals administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var object $league */
/** @var object $tournament */
global $racketmanager;
$and_edit   = '&amp;edit=';
$and_season = '&amp;season=';
$and_league = '&amp;league=';
$and_final  = '&amp;final=';
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : null;
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>
<div class="championship-block">
    <div class="row justify-content-start gx-3 mb-3">
        <div class="col-auto mb-3 mb-md-0">
            <form action="" method="get" class="form-control">
                <input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
                <?php
                if ( empty( $tournament ) ) {
                    ?>
                    <input type="hidden" name="view" value="<?php echo esc_html( $view ); ?>" />
                    <input type="hidden" name="competition_id" value="<?php echo esc_attr( $league->event->competition->id ); ?>" />
                    <input type="hidden" name="league" value="<?php echo esc_html( $league->id ); ?>" />
                    <?php
                } else {
                    ?>
                    <input type="hidden" name="view" value="<?php echo esc_html( $view ); ?>" />
                    <input type="hidden" name="tournament" value="<?php echo esc_attr( $tournament->id ); ?>" />
                    <input type="hidden" name="league" value="<?php echo esc_html( $league->id ); ?>" />
                    <?php
                }
                ?>
                <input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />

                <label for="final" class="visually-hidden"><?php esc_html_e( 'Round', 'racketmanager'); ?></label><select size="1" name="final" id="final">
                    <?php
                    foreach ( $league->championship->get_finals() as $final ) {
                        ?>
                        <option value="<?php echo esc_html( $final['key'] ); ?>" <?php selected( $league->championship->get_current_final_key(), $final['key'] ); ?>><?php echo esc_html( $final['name'] ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <input type="hidden" name="league-tab" value="matches" />
                <input type="submit" class="btn btn-secondary" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
            </form>
        </div>
        <div class="col-12 col-md-auto">
            <form action="" method="get" class="form-control">
                <input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
                <?php
                if ( empty( $tournament ) ) {
                    ?>
                    <input type="hidden" name="view" value="matches" />
                    <input type="hidden" name="competition_id" value="<?php echo esc_attr( $league->event->competition->id ); ?>" />
                    <?php
                } else {
                    ?>
                    <input type="hidden" name="view" value="matches" />
                    <input type="hidden" name="tournament" value="<?php echo esc_attr( $tournament->id ); ?>" />
                    <?php
                }
                ?>
                <input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
                <input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />

                <!-- Bulk Actions -->
                <label>
                    <select name="mode" size="1">
                        <option value="-1" selected="selected"><?php esc_html_e( 'Actions', 'racketmanager' ); ?></option>
                        <option value="edit"><?php esc_html_e( 'Edit Matches', 'racketmanager' ); ?></option>
                    </select>
                </label>

                <label for="final1" class="visually-hidden"><?php esc_html_e( 'Round', 'racketmanager' ); ?></label><select size="1" name="final" id="final1">
                    <?php
                    foreach ( $league->championship->get_finals() as $final ) { ?>
                        <option value="<?php echo esc_html( $final['key'] ); ?>"><?php echo esc_html( $final['name'] ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <input type="hidden" name="league-tab" value="matches" />
                <input type="submit" class="btn btn-secondary" value="<?php esc_html_e( 'Go', 'racketmanager' ); ?>" />
            </form>
        </div>
    </div>
    <?php $final = $league->championship->get_finals( 'current' ); ?>
    <?php
    $matches = $league->get_matches(
        array(
            'final'   => ( ! empty( $final['key'] ) ? $final['key'] : '' ),
            'orderby' => array( 'id' => 'ASC' ),
        )
    );
    ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'racketmanager_update-finals', 'racketmanager_nonce' ); ?>
        <input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
        <input type="hidden" name="season" value="<?php echo esc_html( $league->current_season['name'] ); ?>" />
        <input type="hidden" name="round" value="<?php echo esc_html( $final['round'] ); ?>" />
        <input type="hidden" name="league-tab" value="matches" />
        <input type="hidden" name="action" value="updateFinalResults" />

        <?php
        if ( $matches ) {
            ?>
            <table class="table table-striped table-borderless" aria-describedby="<?php esc_html_e( 'Finals', 'racketmanager' ); ?>">
                <thead>
                    <tr>
                        <th><?php esc_html_e( '#', 'racketmanager' ); ?></th>
                        <th><?php esc_html_e( 'ID', 'racketmanager' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                        <th style="text-align: center;"><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'racketmanager' ); ?></th>
                        <?php
                        if ( $league->event->competition->is_team_entry ) {
                            ?>
                            <th><?php esc_html_e( 'Begin', 'racketmanager' ); ?></th>
                            <?php
                        }
                        if ( isset( $league->num_rubbers ) && $league->num_rubbers > 0 ) {
                            ?>
                            <th><?php echo esc_html__( 'Rubbers', 'racketmanager' ); ?></th>
                            <?php
                        } else {
                            ?>
                            <th colspan="<?php echo esc_html( $league->num_sets ); ?>" style="text-align: center;"><?php echo esc_html__( 'Sets', 'racketmanager' ); ?></th>
                            <?php
                        }
                        ?>
                        <th class="score"><?php esc_html_e( 'Score', 'racketmanager' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list-<?php echo esc_html( $final['key'] ); ?>" class="lm-form-table">
                    <?php
                    $m = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    foreach ( $matches as $match ) {
                        if ( $match->league->event->competition->is_tournament ) {
                            $match_link = 'admin.php?page=racketmanager-tournaments&amp;view=match&amp;tournament=' . $tournament->id . $and_league . $league->id . $and_edit . $match->id . $and_season . $match->season . $and_final . $match->final_round;
                        } elseif ( $match->league->event->competition->is_cup ) {
                            $match_link = 'admin.php?page=racketmanager-cups&amp;view=match&amp;competition_id=' . $match->league->event->competition->id . $and_league . $league->id . $and_edit . $match->id . $and_season . $match->season . $and_final . $match->final_round;
                        } else {
                            $match_link = 'admin.php?page=racketmanager&amp;subpage=match' . $and_league . $league->id . $and_edit . $match->id . $and_season . $match->season;
                        }
                        ?>
                        <tr class="">
                            <td>
                                <?php echo esc_html( $m ); ?><input type="hidden" name="matches[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->id ); ?>" /><input type="hidden" name="home_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->home_team ); ?>" /><input type="hidden" name="away_team[<?php echo esc_html( $match->id ); ?>]" value="<?php echo esc_html( $match->away_team ); ?>" />
                            </td>
                            <td>
                                <?php echo esc_html( $match->id ); ?>
                            </td>
                            <td>
                                <?php echo ( isset( $match->date ) ) ? esc_html( mysql2date( $racketmanager->date_format, $match->date ) ) : 'N/A'; ?>
                            </td>
                            <td class="match-title">
                                <a href="<?php echo esc_html( $match_link ); ?>">
                                    <?php echo esc_html( $match->get_title() ); ?>
                                </a>
                            </td>
                            <td class="match-location">
                                <?php echo ( isset( $match->location ) ) ? esc_html( $match->location ) : 'N/A'; ?>
                            </td>
                            <?php
                            if ( $league->event->competition->is_team_entry ) {
                                ?>
                                <td>
                                    <?php echo ( isset( $match->hour ) ) ? esc_html( mysql2date( $racketmanager->time_format, $match->date ) ) : 'N/A'; ?>
                                </td>
                                <?php
                            }
                            if ( ! empty( $league->num_rubbers ) ) {
                                ?>
                                <td>
                                    <?php
                                    if ( is_numeric( $match->home_team ) && is_numeric( $match->away_team ) && '-1' !== $match->home_team && '-1' !== $match->away_team ) {
                                        ?>
                                        <a class="btn btn-secondary" href="<?php echo esc_attr( $match->link ); ?>result/"><?php esc_html_e( 'View match', 'racketmanager' ); ?></a>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <?php
                            } else {
                                for ( $i = 1; $i <= $league->num_sets; $i++ ) {
                                    if ( ! isset( $match->sets[ $i ] ) ) {
                                        $match->sets[ $i ] = array(
                                            'player1'  => '',
                                            'player2'  => '',
                                            'tiebreak' => '',
                                        );
                                    }
                                    if ( ! isset( $match->sets[ $i ]['tiebreak'] ) ) {
                                        $match->sets[ $i ]['tiebreak'] = '';
                                    }
                                    ?>
                                    <td>
                                        <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player1" class="visually-hidden"><?php esc_html_e( 'Player 1 games', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player1" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player1]" value="<?php echo esc_html( $match->sets[ $i ]['player1'] ); ?>" />
                                        <span>:</span>
                                        <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player2" class="visually-hidden"><?php esc_html_e( 'Player 2 games', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_player2" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][player2]" value="<?php echo esc_html( $match->sets[ $i ]['player2'] ); ?>" />
                                        <br>
                                        <label for="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" class="visually-hidden"><?php esc_html_e( 'Tiebreak', 'racketmanager' ); ?></label><input class="points tie-break" type="text" size="2" id="set_<?php echo esc_html( $match->id ); ?>_<?php echo esc_html( $i ); ?>_tiebreak" name="custom[<?php echo esc_html( $match->id ); ?>][sets][<?php echo esc_html( $i ); ?>][tiebreak]" value="<?php echo esc_html( $match->sets[ $i ]['tiebreak'] ); ?>" />
                                    </td>
                                    <?php
                                }
                            }
                            ?>
                            <td class="score">
                                <label for="home_points-<?php echo esc_html( $match->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Player 1 score', 'racketmanager' ); ?></label><input class="points" type="text" size="2" style="text-align: center;" id="home_points-<?php echo esc_html( $match->id ); ?>" name="home_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo ( isset( $match->home_points ) ) ? esc_html( sprintf( '%g', $match->home_points ) ) : ''; ?>" /> :
                                <label for="away_points-<?php echo esc_html( $match->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Player 2 score', 'racketmanager' ); ?></label><input class="points" type="text" size="2" style="text-align: center;" id="away_points-<?php echo esc_html( $match->id ); ?>" name="away_points[<?php echo esc_html( $match->id ); ?>]" value="<?php echo ( isset( $match->away_points ) ) ? esc_html( sprintf( '%g', $match->away_points ) ) : ''; ?>" />
                            </td>
                        </tr>
                        <?php
                        ++$m;
                    }
                    ?>
                </tbody>
            </table>
            <button class="btn btn-primary"><?php esc_html_e( 'Save Results', 'racketmanager' ); ?></button>
            <?php
        }
        ?>
    </form>
</div>
