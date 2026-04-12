<?php
/**
 * Build match entry for team
 *
 * @package Racketmanager/Templates
 */

namespace RacketManager;

use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

/** @var object $match */
/** @var string $match_status_class */
/** @var string $match_status_text */
/** @var string $match_link */
global $racketmanager;
$match_pending = false;
if ( empty( $match->get_winner_id() ) ) {
    $match_pending = true;
}
?>
<div class="match match--team-match <?php echo empty( $selected_match ) ? '' : 'is-selected'; ?>">
    <?php
    if ( ! empty( $show_header ) ) {
        $league_title = '';
        if ( isset( $dto ) ) {
            $league_title = $dto->league->title;
        } elseif ( isset( $match->league ) ) {
            $league_title = $match->league->title;
        }
        ?>
        <div class="match__header match__header--up">
            <div class="match__header-title">
                <div class="match__header-title-main">
                    <span><?php echo esc_html( $league_title ); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    if ( is_numeric( $match->get_home_team() ) && $match->get_home_team() >= 1 && is_numeric( $match->get_away_team() ) && $match->get_away_team() >= 1 && ! empty( $match_link ) ) {
        ?>
        <a class="team-match__wrapper" href="<?php echo esc_html( $match_link ); ?>">
        <?php
    } else {
        ?>
        <div class="team-match__wrapper">
        <?php
    }
    ?>
    <div class="match__header">
        <span class="match__header-title">
            <?php
            if ( ! empty( $match->get_final() ) ) {
                ?>
                <span><?php echo esc_html( Util::get_final_name( $match->get_final() ) ); ?></span>
                <?php
            } elseif ( ! empty( $match->get_match_day() ) ) {
                ?>
                <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->get_match_day() ); ?></span>
                <?php
            }
            if ( ! empty( $match->get_leg() ) ) {
                ?>
                <span>&nbsp;&#8226&nbsp;<?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->get_leg() ); ?></span>
                <?php
            }
            if ( empty( $by_date ) ) {
                ?>
                &nbsp;&#8226;&nbsp;
                <span>
                    <time
                        datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $match->get_date() ) ); ?></time>
                </span>
                <?php
            }
            ?>
        </span>
        <?php
        if ( $match->get_status() ) {
            $match_message = Util_Lookup::get_match_status( $match->get_status() );
            ?>
            <span class="match__message match-warning"><?php echo esc_html( $match_message ); ?></span>
            <?php
        }
        if ( ! $match_pending && ! empty( $highlight_match ) ) {
            ?>
            <span class="match__status <?php echo esc_attr( $match_status_class ); ?>"><?php echo esc_attr( $match_status_text ); ?></span>
            <?php
        }
        ?>
    </div>
    <div class="match__body">
        <div class="team-match">
            <div class="team-match__name is-team-1">
                <span class="nav--link">
                    <span class="nav-link__value">
                        <?php
                        $home_withdrawn = false;
                        $home_name = '';
                        if ( isset( $dto ) && $dto->home_team ) {
                            $home_name = $dto->home_team->team->get_name();
                            // withdrawn logic here if needed
                        } elseif ( isset( $match->teams['home'] ) ) {
                            $home_name = $match->teams['home']->title;
                            $home_withdrawn = ! empty( $match->teams['home']->is_withdrawn );
                        }

                        if ( $home_withdrawn ) {
                            $title_text = $home_name . ' ' . __( 'has withdrawn', 'racketmanager' );
                            ?>
                            <s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $title_text ); ?>">
                            <?php
                        }
                        echo esc_html( $home_name );
                        if ( $home_withdrawn ) {
                            ?>
                            </s>
                            <?php
                        }
                        ?>
                    </span>
                </span>
            </div>
            <div class="score <?php echo empty( $score_class ) ? '' : esc_attr( $score_class ); ?>">
                <?php
                if ( $match_pending ) {
                    if ( empty( $match->get_start_time() ) ) {
                        $score_filler = __( 'vs', 'racketmanager' );
                    } else {
                        $score_filler = $match->get_start_time();
                    }
                    ?>
                    <time datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php echo esc_html( $score_filler ); ?></time>
                    <?php
                } else {
                    ?>
                    <span class="is-team-1"><?php echo esc_html( sprintf( '%g', $match->get_home_points() ) ); ?></span>
                    <span class="score-separator">-</span>
                    <span class="is-team-2"><?php echo esc_html( sprintf( '%g', $match->get_away_points() ) ); ?></span>
                    <?php
                }
                ?>
            </div>
            <div class="team-match__name is-team-2">
                <span class="nav--link">
                    <span class="nav-link__value">
                        <?php
                        $away_withdrawn = false;
                        $away_name = '';
                        if ( isset( $dto ) && $dto->away_team ) {
                            $away_name = $dto->away_team->team->get_name();
                        } elseif ( isset( $match->teams['away'] ) ) {
                            $away_name = $match->teams['away']->title;
                            $away_withdrawn = ! empty( $match->teams['away']->is_withdrawn );
                        }

                        if ( $away_withdrawn ) {
                            $title_text = $away_name . ' ' . __( 'has withdrawn', 'racketmanager' );
                            ?>
                            <s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
                            <?php
                        }
                        echo esc_html( $away_name );
                        if ( $away_withdrawn ) {
                            ?>
                            </s>
                            <?php
                        }
                        ?>
                    </span>
                </span>
            </div>
        </div>
    </div>
    <?php
    if ( is_numeric( $match->get_home_team() ) && $match->get_home_team() >= 1 && is_numeric( $match->get_away_team() ) && $match->get_away_team() >= 1 && ! empty( $match_link ) ) {
        ?>
        </a>
        <?php
    } else {
        ?>
        </div>
        <?php
    }
    if ( is_numeric( $match->get_home_team() ) && $match->get_home_team() >= 1 && is_numeric( $match->get_away_team() ) && $match->get_away_team() >= 1 && ! empty( $user_can_update ) ) {
        $match_link_result = $match_link . 'result/';
        ?>
        <div class="match__button">
            <a href="<?php echo esc_url( $match_link_result ); ?>" class="btn match__btn">
                <svg width="16" height="16" class="icon ">
                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' ); ?>"></use>
                </svg>
            </a>
        </div>
        <?php
    }
    ?>
</div>
