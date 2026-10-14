<?php
/**
 * Template for Final results
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;

// Preferred input (when included from tournament draw): $vm.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Draw_Page_View_Model ) ? $vm : null;

// BC fallback: if $vm isn't present, rely on locals set by the parent template.
if ( $vm ) {
    $league     = $vm->league;
    $tournament = $vm->tournament;
    $season     = $vm->season;
}

// Presented finals: from the draw view model, or supplied as $finals by the cup/league admin screens.
$finals_list = $vm ? $vm->finals : ( isset( $finals ) && is_array( $finals ) ? $finals : array() );

/** @var object $league */
/** @var object|null $tournament */
/** @var string|null $season */
?>
<div class="championship-block">
    <div class="container draw">
        <div class="row">
            <?php
            $class = null;
            foreach ( $finals_list as $final ) {
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
                                if ( ! is_object( $fixture_details ) ) {
                                    continue;
                                }

                                if ( $fixture_details instanceof Admin_Championship_Final_Fixture_View_Model ) {
                                    $home_team       = $fixture_details->home_name;
                                    $away_team       = $fixture_details->away_name;
                                    $home_class      = $fixture_details->home_class;
                                    $away_class      = $fixture_details->away_class;
                                    $home_tip        = $fixture_details->home_tip;
                                    $away_tip        = $fixture_details->away_tip;
                                    $formatted_score = $fixture_details->formatted_score;
                                } else {
                                    $match = $fixture_details->fixture ?? ( $fixture_details instanceof \stdClass ? $fixture_details : null );
                                    if ( ! isset( $match ) ) {
                                        continue;
                                    }

                                    $home_team_dtls = $fixture_details->home_team->team ?? ( $fixture_details->home_team ?? null );
                                    $away_team_dtls = $fixture_details->away_team->team ?? ( $fixture_details->away_team ?? null );

                                    $home_team = is_object( $home_team_dtls ) && method_exists( $home_team_dtls, 'get_name' )
                                        ? $home_team_dtls->get_name()
                                        : (string) ( $home_team_dtls->title ?? $home_team_dtls->name ?? ( is_string( $home_team_dtls ) ? $home_team_dtls : '' ) );

                                    $away_team = is_object( $away_team_dtls ) && method_exists( $away_team_dtls, 'get_name' )
                                        ? $away_team_dtls->get_name()
                                        : (string) ( $away_team_dtls->title ?? $away_team_dtls->name ?? ( is_string( $away_team_dtls ) ? $away_team_dtls : '' ) );

                                    $home_class = '';
                                    $away_class = '';
                                    $home_tip   = '';
                                    $away_tip   = '';

                                    $winner_id = ! empty( $match->winner_id ) ? (int) $match->winner_id : null;
                                    $home_id   = isset( $match->home_team ) && is_numeric( $match->home_team ) ? (int) $match->home_team : null;
                                    $away_id   = isset( $match->away_team ) && is_numeric( $match->away_team ) ? (int) $match->away_team : null;

                                    if ( null !== $winner_id && null !== $home_id && $winner_id === $home_id ) {
                                        $home_class = 'winner';
                                        $home_tip   = 'Match winner';
                                    } elseif ( null !== $winner_id && null !== $away_id && $winner_id === $away_id ) {
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

                                    $formatted_score = ( isset( $match->home_points ) && null !== $match->home_points && isset( $match->away_points ) && null !== $match->away_points )
                                        ? sprintf( '%d-%d', (int) $match->home_points, (int) $match->away_points )
                                        : '-';
                                }
                                ?>
                                <div class="final-match">
                                    <div class="row">
                                        <div title="<?php echo esc_html( $home_tip ); ?>" class="col-5 col-sm-5 team team-left <?php echo esc_html( $home_class ); ?>">
                                            <?php echo esc_html( $home_team ); ?>
                                        </div>
                                        <div class="col-2 col-sm-2 score">
                                            <?php
                                            if ( '-' !== $formatted_score ) {
                                                ?>
                                                <strong><?php echo esc_html( $formatted_score ); ?></strong>
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
