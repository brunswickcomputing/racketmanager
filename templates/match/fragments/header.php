<?php
declare( strict_types=1 );
/**
 * Template fragment for fixture header
 *
 * @var Fixture_Header_Read_Model $vm
 */

use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;

?>
<?php
if ( $vm->is_shortcode ) {
    ?>
    <div id="match-header" class="team-match-header module module--dark module--card">
    <?php
}
?>
    <div class="module__content">
        <div class="module-container">
            <?php
            if ( $vm->status_message ) {
                ?>
                <div class="text-center">
                <span class="match__message match-warning" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( $vm->status_description ); ?>">
                    <?php echo esc_html( $vm->status_message ); ?>
                </span>
                </div>
                <?php
            }
            ?>

            <div class="text-center">
                <a href="<?php echo esc_url( $vm->event_url ); ?>">
                    <span class="nav-link__value"><?php echo esc_html( $vm->event_name ); ?></span>
                </a>
                <?php
                if ( $vm->league_title ) {
                    ?>
                    &nbsp;&#8226;&nbsp;
                    <a href="<?php echo esc_url( $vm->league_url ); ?>">
                        <span class="nav-link__value"><?php echo esc_html( $vm->league_title ); ?></span>
                    </a>
                    <?php
                }
                ?>

                <div class="text-center">
                    <?php
                    if ( $vm->round_name ) {
                        ?>
                        <span><?php echo esc_html( $vm->round_name ); ?>&nbsp;&#8226</span>
                        <?php
                    } elseif ( $vm->match_day ) {
                        ?>
                        <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $vm->match_day ); ?>&nbsp;&#8226</span>
                        <?php
                    }
                    ?>

                    <?php
                    if ( $vm->leg ) {
                        ?>
                        <span><?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $vm->leg ); ?>&nbsp;&#8226</span>
                        <?php
                    }
                    ?>

                    <span><time datetime="<?php echo esc_attr( $vm->formatted_date ); ?>"><?php echo esc_html( $vm->formatted_date ); ?></time></span>
                </div>

                <?php
                if ( $vm->original_date_formatted ) {
                    ?>
                    <div class="text-center info-msg">
                        <span>(<?php esc_html_e( 'Original scheduled time', 'racketmanager' ); ?>: <?php echo esc_html( $vm->original_date_formatted ); ?>)</span>
                    </div>
                    <?php
                }
                ?>

                <?php
                if ( is_user_logged_in() && $vm->show_menu ) {
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
                                if ( $vm->allow_amend_score ) {
                                    ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo esc_url( $vm->match_link . 'result/' ); ?>">
                                            <?php echo esc_html( $vm->amend_score_label ); ?>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>

                                <?php
                                if ( $vm->allow_schedule_match ) {
                                    ?>
                                    <li>
                                        <a class="dropdown-item matchOptionLink" href="/schedule" data-match-id="<?php echo esc_attr( $vm->id ); ?>" data-match-option="schedule_match">
                                            <?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>

                                <?php
                                if ( $vm->allow_switch_match ) {
                                    ?>
                                    <li>
                                        <a class="dropdown-item matchOptionLink" href="/switch" data-match-id="<?php echo esc_attr( $vm->id ); ?>" data-match-option="switch_home">
                                            <?php esc_html_e( 'Switch home and away', 'racketmanager' ); ?>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>

                                <?php
                                if ( $vm->allow_reset_match_result ) {
                                    ?>
                                    <li>
                                        <a class="dropdown-item matchOptionLink" href="" data-match-id="<?php echo esc_attr( $vm->id ); ?>" data-match-option="reset_fixture_result">
                                            <?php esc_html_e( 'Reset fixture result', 'racketmanager' ); ?>
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
                            <div><?php esc_html_e( 'Home', 'racketmanager' ); ?></div>
                            <h2 class="team-match__name is-team-1" title="<?php echo esc_attr( $vm->home_team_name ); ?>">
                                <a href="<?php echo esc_url( $vm->home_team_url ); ?>" class="nav--link">
                                <span class="nav-link__value">
                                    <?php echo esc_html( $vm->home_team_name ); ?>
                                    <?php
                                    if ( $vm->home_team_withdrawn ) {
                                        ?>
                                        <small>(<?php esc_html_e( 'Withdrawn', 'racketmanager' ); ?>)</small>
                                        <?php
                                    }
                                    ?>
                                </span>
                                </a>
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="score score--large <?php echo esc_attr( $vm->score_class ); ?>">
                    <?php
                    if ( $vm->is_pending ) {
                        ?>
                        <time datetime="<?php echo esc_attr( $vm->fixture_date ); ?>"><?php echo esc_html( $vm->match_time ); ?></time>
                        <?php
                    } else {
                        ?>
                        <span class="is-team-1"><?php echo esc_html( $vm->home_points ); ?></span>
                        <span class="score-separator">-</span>
                        <span class="is-team-2"><?php echo esc_html( $vm->away_points ); ?></span>
                        <?php
                    }
                    ?>
                </div>

                <div class="media media--reverse">
                    <div class="media__wrapper">
                        <div class="media__content">
                            <div><?php esc_html_e( 'Away', 'racketmanager' ); ?></div>
                            <h2 class="team-match__name is-team-2" title="<?php echo esc_attr( $vm->away_team_name ); ?>">
                                <a href="<?php echo esc_url( $vm->away_team_url ); ?>" class="nav--link">
                                <span class="nav-link__value">
                                    <?php echo esc_html( $vm->away_team_name ); ?>
                                    <?php
                                    if ( $vm->away_team_withdrawn ) {
                                        ?>
                                        <small>(<?php esc_html_e( 'Withdrawn', 'racketmanager' ); ?>)</small>
                                        <?php
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
            if ( ! $vm->is_pending ) {
                ?>
                <div class="text-center">
                    <?php esc_html_e( 'Start', 'racketmanager' ); ?>:
                    <time datetime="<?php echo esc_attr( $vm->formatted_date ); ?>"><?php echo esc_html( $vm->match_time ); ?></time>
                </div>
                <?php
            }
            ?>

            <?php
            if ( $vm->edit_mode && $vm->user_can_update && ! $vm->match_approval_mode ) {
                ?>
                <div class="text-center mt-2">
                    <a href="/status" class="nav__link btn btn-outline statusLink" data-match-id="<?php echo esc_attr( $vm->id ); ?>" id="matchStatusButton" data-action="open-match-status-modal">
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
            <span class="module__footer-item-value"><?php echo esc_html( $vm->rubbers_score ); ?></span>
        </span>
            <span class="module__footer-item">
            <strong class="module__footer-item-title"><?php esc_html_e( 'Sets', 'racketmanager' ); ?>: </strong>
            <span class="module__footer-item-value"><?php echo esc_html( $vm->sets_score ); ?></span>
        </span>
            <span class="module__footer-item">
            <strong class="module__footer-item-title"><?php esc_html_e( 'Games', 'racketmanager' ); ?>: </strong>
            <span class="module__footer-item-value"><?php echo esc_html( $vm->games_score ); ?></span>
        </span>
    </div>
    <div id="headerResponse" class="alert_rm alert--danger" style="display: none;">
        <div class="alert__body">
            <div class="alert__body-inner">
                <span id="headerResponseResponse"></span>
            </div>
        </div>
    </div>
    <?php
    require RACKETMANAGER_PATH . 'templates/includes/modal-loading.php';
    ?>
    <?php
    if ( $vm->is_shortcode ) {
        ?>
        </div>
        <?php
        require RACKETMANAGER_PATH . 'templates/includes/match-modal.php';
        require RACKETMANAGER_PATH . 'templates/includes/modal-score.php';
    }
    ?>
