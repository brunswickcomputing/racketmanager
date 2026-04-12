<?php
/**
 * Template for match header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

/** @var Fixture_Details_DTO $dto */
/** @var Fixture $match */
global $racketmanager;
if ( $match->is_pending() ) {
    $score_class = 'is-not-played';
} else {
    $score_class = '';
}
if ( empty( $edit_mode ) ) {
    $edit_mode = false;
} else {
    $edit_mode = true;
}
$is_update_allowed        = $dto->is_update_allowed;
$user_can_update          = $is_update_allowed->user_can_update;
$user_type                = $is_update_allowed->user_type;
$user_team                = $is_update_allowed->user_team;
$match_approval_mode      = $is_update_allowed->match_approval_mode;
$event_season             = $dto->event->get_season_by_name( $match->get_season() );
$allow_schedule_match     = false;
$allow_switch_match       = false;
$allow_amend_score        = false;
$allow_reset_match_result = false;
$show_menu                = false;
$default_match_stat       = '0 - 0';
if ( $match->is_pending() ) {
    if ( ! $edit_mode && $user_can_update ) {
        $allow_amend_score = true;
        $show_menu         = true;
        if ( ( 'admin' === $user_type || 'matchsecretary' === $user_type || 'captain' === $user_type ) && ( 'admin' === $user_type || 'both' === $user_team || 'home' === $user_team ) ) {
            $allow_schedule_match = true;
        }
        if ( ( 'admin' === $user_type || ( 'matchsecretary' === $user_type && ( 'both' === $user_team || 'home' === $user_team ) ) ) && ( ! empty( $event_season['home_away'] ) ) ) {
            $allow_switch_match = true;
        }
    }
} elseif ( ! $edit_mode ) {
    if ( 'admin' === $user_type ) {
        $allow_amend_score  = true;
        $show_menu          = true;
    } elseif ( 'P' === $match->get_confirmed() ) {
        if ( $user_can_update && ! $match_approval_mode ) {
            $allow_amend_score = true;
            $show_menu         = true;
        }
    }
} elseif( 'admin' === $user_type ) {
    $allow_reset_match_result = true;
    $show_menu                = true;
}
?>
<div class="module__content">
    <div class="module-container">
        <?php
        if ( ! empty( $match->get_status() ) ) {
            $match_status = Util_Lookup::get_match_status( $match->get_status() );
            $info_msg     = $match_status;
            switch ( $match->get_status() ) {
                case 1:
                    $team_ref_alt = $match->get_walkover();
                    if ( $team_ref_alt ) {
                        $team_ref = 'home' === $team_ref_alt ? 'away' : 'home';
                        $team     = 'home' === $team_ref ? $dto->home_team : $dto->away_team;
                        if ( $team ) {
                            $info_msg = $match_status . ' - ' . $team->team->get_name() . ' ' . __( 'did not show', 'racketmanager' );
                        }
                    }
                    break;
                case 5:
                    if ( ! empty( $match->get_date_original() ) ) {
                        $info_msg = __( 'Match rescheduled from', 'racketmanager' ) . ' ' . mysql2date( 'j F Y H:i', $match->get_date_original() );
                    }
                    break;
                default:
                    break;
            }
            ?>
            <div class="text-center">
                <span class="match__message match-warning" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( $info_msg ); ?>"><?php echo esc_html( $match_status ); ?></span>
            </div>
            <?php
        }
        ?>
        <div class="text-center">
            <a href="/<?php echo esc_attr( $dto->competition->type ); ?>s/<?php echo esc_attr( seo_url( $dto->event->name ) ); ?>/<?php echo esc_attr( $match->get_season() ); ?>/">
                <span class="nav-link__value"><?php echo esc_html( $dto->event->name ); ?></span>
            </a>
            <?php
            if ( 'cup' !== $dto->competition->type ) {
                ?>
                &nbsp;&#8226;&nbsp;
                <a href="/<?php echo esc_attr( $dto->competition->type ); ?>/<?php echo esc_attr( seo_url( $dto->league->title ) ); ?>/<?php echo esc_attr( $match->get_season() ); ?>/">
                    <span class="nav-link__value"><?php echo esc_html( $dto->league->title ); ?></span>
                </a>
                <?php
            }
            ?>
            <div class="text-center">
                <?php
                if ( ! empty( $match->get_final() ) ) {
                    ?>
                    <span><?php echo esc_html( Util::get_final_name( $match->get_final() ) ); ?>&nbsp;&#8226</span>
                    <?php
                } elseif ( ! empty( $match->get_match_day() ) ) {
                    ?>
                    <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->get_match_day() ); ?>&nbsp;&#8226</span>
                    <?php
                }
                if ( ! empty( $match->get_leg() ) ) {
                    ?>
                    <span><?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->get_leg() ); ?>&nbsp;&#8226</span>
                    <?php
                }
                ?>
                <span><time datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $match->get_date() ) ); ?></time></span>
            </div>
            <?php
            if ( ! empty( $match->get_date_original() ) ) {
                ?>
                <div class="text-center info-msg">
                    <span>(<?php esc_html_e( 'Original scheduled time', 'racketmanager' ); ?>: <?php echo esc_html( mysql2date( 'j F Y H:i', $match->get_date_original() ) ); ?>)</span>
            </div>
                <?php
            }
            ?>
            <?php
            if ( is_user_logged_in() && $show_menu ) {
                ?>
                <div class="match__change">
                    <div class="dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg width="16" height="16" class="icon ">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
                            </svg>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
                            if ( $allow_amend_score ) {
                                $match_link = $dto->link . 'result/';
                                ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo esc_url( $match_link ); ?>">
                                        <?php
                                        if ( $match->is_pending() ) {
                                            esc_html_e( 'Enter result', 'racketmanager' );
                                        } else {
                                            esc_html_e( 'Adjust team score', 'racketmanager' );
                                        }
                                        ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $allow_schedule_match ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="/schedule" data-match-id="<?php echo esc_attr( $match->get_id() ); ?>" data-match-option="schedule_match">
                                        <?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $allow_switch_match ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="/switch" data-match-id="<?php echo esc_attr( $match->get_id() ); ?>" data-match-option="switch_home">
                                        <?php esc_html_e( 'Switch home and away', 'racketmanager' ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $allow_reset_match_result ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="" data-match-id="<?php echo esc_attr( $match->get_id() ); ?>" data-match-option="reset_match_result">
                                        <?php esc_html_e( 'Reset match result', 'racketmanager' ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="team-match mt-3">
            <div class="media">
                <div class="media__wrapper">
                    <div class="media__content">
                        <?php
                        if ( empty( $dto->fixture->get_host() ) ) {
                            ?>
                            <div><?php esc_html_e( 'Home', 'racketmanager' ); ?></div>
                            <?php
                        }
                        ?>
                        <h2 class="team-match__name is-team-1" title="<?php echo esc_html( $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' ) ); ?>">
                            <a href="/<?php echo esc_attr( $dto->competition->type ); ?>/<?php echo esc_html( seo_url( $dto->league->title ) ); ?>/<?php echo esc_attr( $match->get_season() ); ?>/team/<?php echo esc_attr( seo_url( $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' ) ) ); ?>/" class="nav--link">
                                <span class="nav-link__value">
                                    <?php
                                    $home_name = $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' );
                                    echo esc_html( $home_name );
                                    if ( $dto->home_team && $dto->home_team->is_withdrawn ) {
                                        echo ' <small>(' . esc_html__( 'Withdrawn', 'racketmanager' ) . ')</small>';
                                    }
                                    ?>
                                </span>
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="score score--large <?php echo esc_attr( $score_class ); ?>">
                <?php
                if ( $match->is_pending() ) {
                    ?>
                    <time datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php the_match_time( $match->get_start_time() ); ?></time>
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
            <div class="media media--reverse">
                <div class="media__wrapper">
                    <div class="media__content">
                        <?php
                        if ( empty( $dto->fixture->get_host() ) ) {
                            ?>
                            <div><?php esc_html_e( 'Away', 'racketmanager' ); ?></div>
                            <?php
                        }
                        ?>
                        <h2 class="team-match__name is-team-2" title="<?php echo esc_html( $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' ) ); ?>">
                            <a href="/<?php echo esc_attr( $dto->competition->type ); ?>/<?php echo esc_html( seo_url( $dto->league->title ) ); ?>/<?php echo esc_attr( $match->get_season() ); ?>/team/<?php echo esc_attr( seo_url( $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' ) ) ); ?>/" class="nav--link">
                                <span class="nav-link__value">
                                    <?php
                                    $away_name = $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' );
                                    echo esc_html( $away_name );
                                    if ( $dto->away_team && $dto->away_team->is_withdrawn ) {
                                        echo ' <small>(' . esc_html__( 'Withdrawn', 'racketmanager' ) . ')</small>';
                                    }
                                    ?>
                                </span>
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if ( ! $match->is_pending() ) {
            ?>
            <div class="text-center">
                <?php esc_html_e( 'Start', 'racketmanager' ); ?>: <time datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php the_match_time( $match->get_start_time() ); ?></time>
            </div>
            <?php
        }
        if ( $edit_mode && $user_can_update && ! $match_approval_mode ) {
            ?>
            <div class="text-center mt-2">
                <a href="/status" class="nav__link btn btn-outline statusLink" data-match-id="<?php echo esc_attr( $match->get_id() ); ?>" id="matchStatusButton" data-action="open-match-status-modal">
                    <svg width="16" height="16" class="icon-plus nav-link__prefix">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#plus-lg' ); ?>"></use>
                    </svg>
                    <span class="nav-link__value"><?php esc_html_e( 'Match status', 'racketmanager' ); ?></span>
                </a>

            </div>
            <?php
        }
        ?>
    </div>
</div>
<div class="module__footer">
    <span class="module__footer-item">
        <strong class="module__footer-item-title"><?php esc_html_e( 'Rubbers', 'racketmanager' ); ?>: </strong>
        <?php
        if ( isset( $match->get_custom()['stats']['rubbers'] ) ) {
            $match_stat = $match->get_custom()['stats']['rubbers']['home'] . ' - ' . $match->get_custom()['stats']['rubbers']['away'];
        } else {
            $match_stat = $default_match_stat;
        }
        ?>
        <span class="module__footer-item-value"><?php echo esc_html( $match_stat ); ?></span>
    </span>
    <span class="module__footer-item">
        <strong class="module__footer-item-title"><?php esc_html_e( 'Sets', 'racketmanager' ); ?>: </strong>
        <?php
        if ( isset( $match->get_custom()['stats']['sets'] ) ) {
            $match_stat = $match->get_custom()['stats']['sets']['home'] . ' - ' . $match->get_custom()['stats']['sets']['away'];
        } else {
            $match_stat = $default_match_stat;
        }
        ?>
        <span class="module__footer-item-value"><?php echo esc_html( $match_stat ); ?></span>
    </span>
    <span class="module__footer-item">
        <strong class="module__footer-item-title"><?php esc_html_e( 'Games', 'racketmanager' ); ?>: </strong>
        <?php
        if ( isset( $match->get_custom()['stats']['games'] ) ) {
            $match_stat = $match->get_custom()['stats']['games']['home'] . ' - ' . $match->get_custom()['stats']['games']['away'];
        } else {
            $match_stat = $default_match_stat;
        }
        ?>
        <span class="module__footer-item-value"><?php echo esc_html( $match_stat ); ?></span>
    </span>
</div>
<div id="headerResponse" class="alert_rm alert--danger" style="display: none;">
    <div class="alert__body">
        <div class="alert__body-inner">
            <span id="headerResponseText"></span>
        </div>
    </div>
</div>
<?php require RACKETMANAGER_PATH . 'templates/includes/modal-loading.php'; ?>
