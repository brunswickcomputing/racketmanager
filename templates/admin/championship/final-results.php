<?php
/**
 * Template for Final results
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;

// Preferred input (when included from tournament draw): $vm.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Draw_Page_View_Model ) ? $vm : null;

// BC fallback: if $vm isn't present, rely on locals set by the parent template.
if ( $vm ) {
    $league     = $vm->league;
    $tournament = $vm->tournament;
    $season     = $vm->season;
}

/** @var object $league */
/** @var object|null $tournament */
/** @var string|null $season */
?>
<div class="championship-block">
    <div class="container draw">
        <div class="row">
            <?php
            $class = null;
            foreach ( $vm->finals as $final ) {
                $final = (array) $final;
                $class = ( 'alternate' === $class ) ? '' : 'alternate';
                ?>
                <div class="final-round <?php echo esc_html( $class ); ?>">
                    <div class="roundName">
                        <?php echo esc_html( $final['name'] ); ?>
                    </div>
                    <div class="container round-matches">
                        <?php
                        if ( $final['num_matches'] < 4 ) {
                            $sm_size = $final['num_matches'];
                            $lg_size = $sm_size;
                        } else {
                            $sm_size = 2;
                            $lg_size = 4;
                        }

                        ?>
                        <div class="row row-cols-1 row-cols-sm-<?php echo esc_html( $sm_size ); ?> row-cols-lg-<?php echo esc_html( $lg_size ); ?> final-matches justify-content-center">
                            <?php
                            foreach ( $final['fixtures'] as $fixture_details ) {
                                $match = $fixture_details->fixture;
                                $league  = $fixture_details->league;
                                $event   = $fixture_details->event;
                                $competition = $fixture_details->competition;
                                $home_team_dtls = $fixture_details->home_team->team ?? null;
                                $away_team_dtls = $fixture_details->away_team->team ?? null;
                                ?>
                                <div class="final-match">
                                    <div class="row">
                                        <?php
                                        if ( isset( $match ) ) {
                                            $home_class = '';
                                            $away_class = '';
                                            $home_tip   = '';
                                            $away_tip   = '';
                                            if ( $match->winner_id === intval( $match->home_team ) ) {
                                                $home_class = 'winner';
                                                $home_tip   = 'Match winner';
                                            } elseif ( $match->winner_id === intval( $match->away_team ) ) {
                                                $away_class = 'winner';
                                                $away_tip   = 'Match winner';
                                            } elseif ( isset( $match->host ) ) {
                                                if ( 'home' === $match->host ) {
                                                    $home_class = 'host';
                                                    $home_tip   = 'Home team';
                                                } elseif ( 'away' === $match->host ) {
                                                    $away_class = 'host';
                                                    $away_tip   = 'Home team';
                                                }
                                            }
                                            $home_team = $home_team_dtls->title ?? null;
                                            $away_team = $away_team_dtls->title ?? null;
                                            ?>
                                            <div title="<?php echo esc_html( $home_tip ); ?>" class="col-5 col-sm-5 team team-left <?php echo esc_html( $home_class ); ?>">
                                                <?php echo esc_html( $home_team ); ?>
                                            </div>
                                            <div class="col-2 col-sm-2 score">
                                                <?php
                                                if ( null !== $match->home_points && null !== $match->away_points ) {
                                                    ?>
                                                    <strong><?php echo esc_html( sprintf( '%d-%d', $match->home_points, $match->away_points ) ); ?></strong>
                                                    <?php
                                                } else {
                                                    ?>
                                                    -
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <div title="<?php echo esc_html( $away_tip ); ?>" class="col-5 col-sm-5 team team-right <?php echo esc_html( $away_class ); ?>">
                                                <?php echo esc_html( $away_team ); ?>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
