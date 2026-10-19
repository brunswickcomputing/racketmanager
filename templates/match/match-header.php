<?php
/**
 * Template for match header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;
/** @var Fixture_Header_Read_Model $model */
$header = $model;
?>
<div class="module__content">
    <div class="module-container">
        <?php
        if ( ! empty( $header->status_message ) ) {
            ?>
            <div class="text-center">
                <span class="match__message match-warning" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( $header->status_description ?? $header->status_message ); ?>"><?php echo esc_html( $header->status_message ); ?></span>
            </div>
            <?php
        }
        ?>
        <div class="text-center">
            <a href="<?php echo esc_url( $header->event_url ); ?>">
                <span class="nav-link__value"><?php echo esc_html( $header->event_name ); ?></span>
            </a>
            <?php
            if ( ! empty( $header->league_title ) ) {
                ?>
                &nbsp;&#8226;&nbsp;
                <a href="<?php echo esc_url( $header->league_url ); ?>">
                    <span class="nav-link__value"><?php echo esc_html( $header->league_title ); ?></span>
                </a>
                <?php
            }
            ?>
            <div class="text-center">
                <?php
                if ( ! empty( $header->round_name ) ) {
                    ?>
                    <span><?php echo esc_html( $header->round_name ); ?>&nbsp;&#8226</span>
                    <?php
                } elseif ( ! empty( $header->match_day ) ) {
                    ?>
                    <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( (string) $header->match_day ); ?>&nbsp;&#8226</span>
                    <?php
                }
                if ( ! empty( $header->leg ) ) {
                    ?>
                    <span><?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( (string) $header->leg ); ?>&nbsp;&#8226</span>
                    <?php
                }
                ?>
                <span><time datetime="<?php echo esc_attr( $header->fixture_date ); ?>"><?php echo esc_html( $header->formatted_date ); ?></time></span>
            </div>
            <?php
            if ( ! empty( $header->original_date_formatted ) ) {
                ?>
                <div class="text-center info-msg">
                    <span>(<?php esc_html_e( 'Original scheduled time', 'racketmanager' ); ?>: <?php echo esc_html( $header->original_date_formatted ); ?>)</span>
                </div>
                <?php
            }
            ?>
            <?php
            if ( $header->show_menu ) {
                ?>
                <div class="match__change">
                    <div class="dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg width="16" height="16" class="icon ">
                                <use xlink:href="<?php echo esc_url( $header->pencil_icon_url ); ?>"></use>
                            </svg>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
                            if ( $header->allow_amend_score ) {
                                ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo esc_url( $header->match_link . 'result/' ); ?>">
                                        <?php echo esc_html( $header->amend_score_label ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $header->allow_schedule_match ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="/schedule" data-match-id="<?php echo esc_attr( (string) $header->id ); ?>" data-match-option="schedule_match">
                                        <?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $header->allow_switch_match ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="/switch" data-match-id="<?php echo esc_attr( (string) $header->id ); ?>" data-match-option="switch_home">
                                        <?php esc_html_e( 'Switch home and away', 'racketmanager' ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                            <?php
                            if ( $header->allow_reset_match_result ) {
                                ?>
                                <li>
                                    <a class="dropdown-item matchOptionLink" href="" data-match-id="<?php echo esc_attr( (string) $header->id ); ?>" data-match-option="reset_match_result">
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
                        if ( $header->show_home_away_labels ) {
                            ?>
                            <div><?php esc_html_e( 'Home', 'racketmanager' ); ?></div>
                            <?php
                        }
                        ?>
                        <h2 class="team-match__name is-team-1" title="<?php echo esc_html( $header->home_team_name ); ?>">
                            <a href="<?php echo esc_url( $header->home_team_url ); ?>" class="nav--link">
                                <span class="nav-link__value">
                                    <?php
                                    echo esc_html( $header->home_team_name );
                                    if ( $header->home_team_withdrawn ) {
                                        echo ' <small>(' . esc_html__( 'Withdrawn', 'racketmanager' ) . ')</small>';
                                    }
                                    ?>
                                </span>
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="score score--large <?php echo esc_attr( $header->score_class ); ?>">
                <?php
                if ( $header->is_pending ) {
                    ?>
                    <time datetime="<?php echo esc_attr( $header->fixture_date ); ?>"><?php echo esc_html( $header->start_time_display ); ?></time>
                    <?php
                } else {
                    ?>
                    <span class="is-team-1"><?php echo esc_html( $header->home_points ); ?></span>
                    <span class="score-separator">-</span>
                    <span class="is-team-2"><?php echo esc_html( $header->away_points ); ?></span>
                    <?php
                }
                ?>
            </div>
            <div class="media media--reverse">
                <div class="media__wrapper">
                    <div class="media__content">
                        <?php
                        if ( $header->show_home_away_labels ) {
                            ?>
                            <div><?php esc_html_e( 'Away', 'racketmanager' ); ?></div>
                            <?php
                        }
                        ?>
                        <h2 class="team-match__name is-team-2" title="<?php echo esc_html( $header->away_team_name ); ?>">
                            <a href="<?php echo esc_url( $header->away_team_url ); ?>" class="nav--link">
                                <span class="nav-link__value">
                                    <?php
                                    echo esc_html( $header->away_team_name );
                                    if ( $header->away_team_withdrawn ) {
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
        if ( ! $header->is_pending ) {
            ?>
            <div class="text-center">
                <?php esc_html_e( 'Start', 'racketmanager' ); ?>: <time datetime="<?php echo esc_attr( $header->fixture_date ); ?>"><?php echo esc_html( $header->start_time_display ); ?></time>
            </div>
            <?php
        }
        if ( $header->edit_mode && $header->user_can_update && ! $header->match_approval_mode ) {
            ?>
            <div class="text-center mt-2">
                <a href="/status" class="nav__link btn btn-outline statusLink" data-match-id="<?php echo esc_attr( (string) $header->id ); ?>" id="matchStatusButton" data-action="open-match-status-modal">
                    <svg width="16" height="16" class="icon-plus nav-link__prefix">
                        <use xlink:href="<?php echo esc_url( $header->plus_icon_url ); ?>"></use>
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
        <span class="module__footer-item-value"><?php echo esc_html( $header->rubbers_score ); ?></span>
    </span>
    <span class="module__footer-item">
        <strong class="module__footer-item-title"><?php esc_html_e( 'Sets', 'racketmanager' ); ?>: </strong>
        <span class="module__footer-item-value"><?php echo esc_html( $header->sets_score ); ?></span>
    </span>
    <span class="module__footer-item">
        <strong class="module__footer-item-title"><?php esc_html_e( 'Games', 'racketmanager' ); ?>: </strong>
        <span class="module__footer-item-value"><?php echo esc_html( $header->games_score ); ?></span>
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
