<?php
/**
 * Match card template for rubbers
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $sponsor_html */
$points_span = 2 + intval( $match->league->num_sets );
?>
        <div id="matchRubbers" class="rubber-block">
            <div id="matchHeader">
                <div class="leagueTitle"><?php echo esc_html( $match->league->title ); ?></div>
                <div class="matchDate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
                <div class="matchday">
                    <?php
                    if ( $match->league->event->competition->is_championship ) {
                        echo esc_html( Racketmanager_Util::get_final_name( $match->final_round ) );
                    } else {
                        echo 'Week' . esc_html( $match->match_day );
                    }
                    ?>
                </div>
                <div class="matchTitle"><?php echo esc_html( $match->match_title ); ?></div>
            </div>
            <form id="match-rubbers" action="#" method="post">
                <?php wp_nonce_field( 'rubbers-match' ); ?>

                <table class="table table-bordered" aria-describedby="?php esc_html_e( 'Match card', 'racketmanager' ); ?>">
                    <thead class="table-dark">
                        <tr>
                            <th style="text-align: center;"><?php esc_html_e( 'Pair', 'racketmanager' ); ?></th>
                            <th style="text-align: center;" colspan="1"><?php esc_html_e( 'Home Team', 'racketmanager' ); ?></th>
                            <th style="text-align: center;" colspan="<?php echo esc_html( $match->league->num_sets ); ?>"><?php esc_html_e( 'Sets', 'racketmanager' ); ?></th>
                            <th style="text-align: center;" colspan="1"><?php esc_html_e( 'Away Team', 'racketmanager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody class="" id="the-list-rubbers-<?php echo esc_html( $match->id ); ?>" >

                        <?php
                        $match->rubbers = $match->get_rubbers();
                        $r              = 0;

                        foreach ( $match->rubbers as $rubber ) {
                            ?>
                            <tr class="rtr">
                                <td rowspan="3" class="rtd centered">
                                    <?php echo isset( $rubber->rubber_number ) ? esc_html( $rubber->rubber_number ) : ''; ?>
                                </td>
                                <td class="rtd">
                                    <label for="home_player_1_<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Home player 1', 'racketmanager' ); ?></label><input class="player" name="home_player_1[<?php echo esc_html( $r ); ?>]" id="home_player_1_<?php echo esc_html( $r ); ?>" />
                                </td>

                                <?php for ( $i = 1; $i <= $match->league->num_sets; $i++ ) { ?>
                                    <td rowspan="2" class="rtd">
                                        <label for="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player1" class="visually-hidden"><?php esc_html_e( 'Home sets', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player1" name="custom[<?php echo esc_html( $r ); ?>][sets][<?php echo esc_html( $i ); ?>][player1]" />
                                        :
                                        <label for="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player2" class="visually-hidden"><?php esc_html_e( 'Away se', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $r ); ?>_<?php echo esc_html( $i ); ?>_player2" name="custom[<?php echo esc_html( $r ); ?>][sets][<?php echo esc_html( $i ); ?>][player2]" />
                                    </td>
                                <?php } ?>

                                <td class="rtd">
                                    <label for="away_player_1_<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Away player 1', 'racketmanager' ); ?></label><input class="player" name="away_player_1[<?php echo esc_html( $r ); ?>]" id="away_player_1_<?php echo esc_html( $r ); ?>" />
                                </td>
                            </tr>
                            <tr class="rtr">
                                <td class="rtd">
                                    <label for="home_player_2_<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Home player 2', 'racketmanager' ); ?></label><input class="player" name="home_player_2[<?php echo esc_html( $r ); ?>]" id="home_player_2_<?php echo esc_html( $r ); ?>" />
                                </td>
                                <td class="rtd">
                                    <label for="away_player_2_<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Away player 2', 'racketmanager' ); ?></label><input class="player" name="away_player_2[<?php echo esc_html( $r ); ?>]" id="away_player_2_<?php echo esc_html( $r ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="<?php echo esc_html( $points_span ); ?>" class="rtd" style="text-align: center;">
                                    <label for="home_points-<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Home points', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="home_points-<?php echo esc_html( $r ); ?>" name="home_points[<?php echo esc_html( $r ); ?>]" />
                                    :
                                    <label for="away_points-<?php echo esc_html( $r ); ?>" class="visually-hidden"><?php esc_html_e( 'Away points', 'racketmanager' ); ?></label><input class="points" type="text" size="2" id="away_points-<?php echo esc_html( $r ); ?>" name="away_points[<?php echo esc_html( $r ); ?>]" />
                                </td>
                            </tr>
                            <?php
                            ++$r;
                        }
                        ?>
                        <tr>
                            <td class="rtd centered">
                            </td>
                            <td class="rtd">
                                <label for="home_sig" class="visually-hidden"><?php esc_html_e( 'Home signature', 'racketmanager' ); ?></label><input class="player" name="home_sig" id="home_sig" placeholder="Home Captain Signature" />
                            </td>
                            <td colspan="<?php echo intval( $match->league->num_sets ); ?>" class="rtd" style="text-align: center;">
                                <input class="points" type="text" size="2" id="home_points-<?php echo esc_html( $r ); ?>" name="home_points[<?php echo esc_html( $r ); ?>]" />
                                :
                                <input class="points" type="text" size="2" id="away_points-<?php echo esc_html( $r ); ?>" name="away_points[<?php echo esc_html( $r ); ?>]" />
                            </td>
                            <td class="rtd">
                                <label for="away_sig" class="visually-hidden"><?php esc_html_e( 'Home signature', 'racketmanager' ); ?></label><input class="player" name="away_sig" id="away_sig" placeholder="Away Captain Signature" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <?php echo $sponsor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
