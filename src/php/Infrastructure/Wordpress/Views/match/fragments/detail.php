<?php
/**
 * Template fragment for fixture detail (rubbers and approvals)
 *
 * @var Fixture_Detail_Read_Model $model
 */

use Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model;

?>
<?php
if ( $model->is_standalone ) {
?>
<div id="matchRubbers" class="row">
    <div class="page-content__main <?php echo $model->location || $model->teams ? 'col-12 col-lg-8' : 'col-12'; ?>">
        <div class="module module--card">
            <div class="module__banner">
                <h3 class="module__title">
                    <?php esc_html_e( 'Match Rubbers', 'racketmanager' ); ?>
                </h3>
                <div class="module__aside">
                    <?php
                    if ( $model->show_print_button ) {
                        ?>
                        <button type="button" class="btn btn--link match-print" id="printMatchCard" data-match-id="<?php echo esc_attr( $model->match_id ); ?>" data-print-match-card="<?php echo esc_attr( $model->match_id ); ?>" data-bs-toggle="tooltip"
                           data-bs-placement="top" title="<?php esc_html_e( 'Print match card', 'racketmanager' ); ?>">
                            <svg width="16" height="16" class="icon ">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#printer-fill' ); ?>"></use>
                            </svg>
                        </button>
                        <?php
                    }
                    ?>
                    <?php
                    if ( $model->show_edit_button ) {
                        ?>
                        <div class="match-mode" id="editMatchMode">
                            <a class="btn btn--link" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Edit', 'racketmanager' ); ?>" href="<?php echo esc_url( $model->edit_url ); ?>">
                                <svg width="16" height="16" class="icon ">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
                                </svg>
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="module__content">
                <?php
                }
                ?>
                <div class="module-container">
                    <ul class="match-group">
                        <?php
                        foreach ( $model->rubbers as $rubber ) {
                            ?>
                            <li class="match-group__item">
                                <div class="match" id="rubber-<?php echo esc_attr( $rubber['id'] ); ?>">
                                    <div class="match__header">
                                        <ul class="match__header-title">
                                            <li class="match__header-title-item">
                                                    <span title="<?php echo esc_attr( $rubber['title'] ); ?>" class="nav--link">
                                                        <span class="nav-link__value"><?php echo esc_html( $rubber['title'] ); ?></span>
                                                    </span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="match__body">
                                        <div class="match__row-wrapper">
                                            <?php
                                            foreach ( [ 'home', 'away' ] as $opponent_key ) {
                                                ?>
                                                <?php $opponent = $rubber['opponents'][ $opponent_key ]; ?>
                                                <div class="match__row">
                                                    <div class="match__row-title">
                                                        <div class="match__row-title-header">
                                                            <?php echo esc_html( $opponent['team_name'] ); ?>
                                                        </div>
                                                        <?php
                                                        foreach ( $opponent['players'] as $player ) {
                                                            ?>
                                                            <div class="match__row-title-value">
                                                                <span class="match__row-title-value-content">
                                                                    <span class="nav-link__value <?php echo esc_attr( $opponent['status_class'] ); ?>">
                                                                        <?php
                                                                        if ( $player['url'] ) {
                                                                            ?>
                                                                            <a href="<?php echo esc_url( $player['url'] ); ?>">
                                                                        <?php
                                                                        }
                                                                        ?>
                                                                        <span class="<?php echo esc_attr( $player['class'] ); ?>"
                                                                            <?php
                                                                            if ( $player['description'] ) {
                                                                                ?>
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php echo esc_attr( $player['description'] ); ?>"
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        >
                                                                            <?php echo esc_html( $player['name'] ); ?>
                                                                        </span>

                                                                        <?php
                                                                        if ( $player['url'] ) {
                                                                        ?>
                                                                            </a>
                                                                             <?php
                                                                        }
                                                                        ?>
                                                                    </span>
                                                                </span>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>

                                                    <?php
                                                    if ( $opponent['message'] ) {
                                                        ?>
                                                        <span class="match__message match-warning">
                                                            <?php echo esc_html( $opponent['message'] ); ?>
                                                        </span>
                                                        <?php
                                                    }
                                                    ?>

                                                    <?php
                                                    if ( $opponent['status_text'] ) {
                                                        ?>
                                                        <span class="match__status <?php echo esc_attr( $opponent['status_class'] ); ?>">
                                                            <?php echo esc_html( $opponent['status_text'] ); ?>
                                                        </span>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>

                                        <?php
                                        foreach ( $rubber['sets'] as $set ) {
                                            ?>
                                            <ul class="match-points">
                                                <li class="match-points__cell <?php echo $set['home_won'] ? 'winner' : ''; ?>">
                                                    <?php echo esc_html( $set['home'] ); ?>
                                                    <?php
                                                    if ( $set['tiebreak'] && $set['home_won'] ) {
                                                        ?>
                                                        <span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
                                                        <?php
                                                    }
                                                    ?>
                                                </li>
                                                <li class="match-points__cell <?php echo $set['away_won'] ? 'winner' : ''; ?>">
                                                    <?php echo esc_html( $set['away'] ); ?>
                                                    <?php
                                                    if ( $set['tiebreak'] && $set['away_won'] ) {
                                                        ?>
                                                        <span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
                                                        <?php
                                                    }
                                                    ?>
                                                </li>
                                            </ul>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                    $has_approver = false;
                    foreach ( $model->approvals as $approval ) {
                        if ( $approval['approver_name'] ) {
                            $has_approver = true;
                            break;
                        }
                    }

                    if ( $has_approver ) {
                        ?>
                        <div class="mt-3" id="approvals">
                            <div class="match">
                                <div class="match__header">
                                    <ul class="match__header-title">
                                        <li class="match__header-title-item">
                                            <span class="nav-link__value"><?php esc_html_e( 'Approvals', 'racketmanager' ); ?></span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="match__body">
                                    <div class="match__row-wrapper">
                                        <?php
                                        foreach ( [ 'home', 'away' ] as $opponent_key ) {
                                            ?>
                                            <?php $approval = $model->approvals[ $opponent_key ]; ?>
                                            <div class="match__row">
                                                <div class="match__row-title">
                                                    <div class="match__row-title-header">
                                                        <?php echo esc_html( $approval['team_name'] ); ?>
                                                    </div>
                                                    <?php
                                                    if ( $approval['approver_name'] ) {
                                                        ?>
                                                        <div class="match__row-title-value">
                                                            <span class="match__row-title-value-content">
                                                                <span class="nav-link__value"><?php echo esc_html( $approval['approver_name'] ); ?></span>
                                                            </span>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                    <?php
                                                    if ( $approval['comment'] ) {
                                                        ?>
                                                        <div class="match__row-title-value">
                                                            <span class="match__row-title-value-content">
                                                                <span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>">
                                                                    <?php echo esc_html( $approval['comment'] ); ?>
                                                                </span>
                                                            </span>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                        <?php
                                        if ( $model->general_comments ) {
                                            ?>
                                            <div class="match__row match__row-comments">
                                                <div class="match__row-title">
                                                    <div class="match__row-title-header">
                                                        <?php esc_html_e( 'Comments', 'racketmanager' ); ?>
                                                    </div>
                                                    <div class="match__row-title-value">
                                                        <span class="match__row-title-value-content">
                                                            <span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>">
                                                                <?php echo esc_html( $model->general_comments ); ?>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                </div>

                <?php
                if ( $model->is_standalone ) {
                ?>
            </div>
        </div>
    </div>

    <?php
    if ( $model->location || $model->teams ) {
        ?>
        <div class="page-content__sidebar col-12 col-lg-4">
            <div class="row">
                <?php
                if ( $model->location ) {
                    ?>
                    <div class="col-12 col-sm-6 col-lg-12">
                        <div class="module module--card">
                            <div class="module__banner">
                                <h3 class="module__title">
                                    <?php esc_html_e( 'Location', 'racketmanager' ); ?>
                                </h3>
                            </div>
                            <div class="module__content">
                                <div class="module-container">
                                    <h5 class="subheading">
                                        <?php echo esc_html( $model->location['club_name'] ); ?>
                                    </h5>
                                    <ul class="list list--naked">
                                        <?php
                                        if ( $model->location['address'] ) {
                                            ?>
                                            <li class="list__item">
                                                <svg width="16" height="16" class="icon icon-marker">
                                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons.svg#icon-marker' ); ?>"></use>
                                                </svg>
                                                <span class="nav-link__value">
                                                    <?php echo esc_html( $model->location['address'] ); ?>
                                                </span>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if ( $model->location['match_secretary_name'] ) {
                                            ?>
                                            <li class="list__item">
                                                <svg width="16" height="16" class="icon icon-captain">
                                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons.svg#icon-captain' ); ?>"></use>
                                                </svg>
                                                <span class="">
                                                    <?php echo esc_html( $model->location['match_secretary_name'] ); ?>
                                                </span>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if ( is_user_logged_in() ) {
                                            if ( $model->location['match_secretary_contactno'] ) {
                                                ?>
                                                <li class="list__item">
                                                    <a href="tel:<?php echo esc_attr( $model->location['match_secretary_contactno'] ); ?>" class="nav--link" rel="nofollow">
                                                        <svg width="16" height="16" class="icon ">
                                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
                                                        </svg>
                                                        <span class="nav--link">
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html( $model->location['match_secretary_contactno'] ); ?>
                                                            </span>
                                                        </span>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            if ( $model->location['match_secretary_email'] ) {
                                                ?>
                                                <li class="list__item">
                                                    <a href="mailto:<?php echo esc_attr( $model->location['match_secretary_email'] ); ?>" class="nav--link">
                                                        <svg width="16" height="16" class="icon ">
                                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
                                                        </svg>
                                                        <span class="nav--link">
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html( $model->location['match_secretary_email'] ); ?>
                                                            </span>
                                                        </span>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                        <?php
                                        if ( $model->location['website'] ) {
                                            ?>
                                            <li class="list__item">
                                                <a href="<?php echo esc_url( $model->location['website'] ); ?>" class="nav--link" target="_blank" rel="noopener nofollow">
                                                    <svg width="16" height="16" class="icon icon-globe">
                                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#globe' ); ?>"></use>
                                                    </svg>
                                                    <span class="">
                                                        <?php echo esc_html( $model->location['website'] ); ?>
                                                    </span>
                                                </a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <?php
                if ( $model->teams ) {
                    ?>
                    <div class="col-12 col-sm-6 col-lg-12">
                        <div class="module module--card">
                            <div class="module__banner">
                                <h3 class="module__title">
                                    <?php esc_html_e( 'Team Captains', 'racketmanager' ); ?>
                                </h3>
                            </div>
                            <div class="module__content">
                                <div class="module-container">
                                    <?php
                                    foreach ( [ 'home', 'away' ] as $opponent ) {
                                        ?>
                                        <?php
                                        if ( isset( $model->teams[ $opponent ] ) ) {
                                            ?>
                                            <?php $team = $model->teams[ $opponent ]; ?>
                                            <h5 class="subheading">
                                                <?php echo esc_html( $team['captain_name'] ); ?>
                                            </h5>
                                            <ul class="list list--naked">
                                                <li class="list__item">
                                                    <svg width="16" height="16" class="icon-team">
                                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons-extra.svg#icon-team' ); ?>"></use>
                                                    </svg>
                                                    <span class=""><?php echo esc_html( $team['team_name'] ); ?></span>
                                                </li>
                                                <?php
                                                if ( is_user_logged_in() ) {
                                                    if ( $team['contactno'] ) {
                                                        ?>
                                                        <li class="list__item">
                                                            <a href="tel:<?php echo esc_attr( $team['contactno'] ); ?>" class="nav--link" rel="nofollow">
                                                                <svg width="16" height="16" class="icon ">
                                                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
                                                                </svg>
                                                                <span class="nav--link">
                                                                    <span class="nav-link__value">
                                                                        <?php echo esc_html( $team['contactno'] ); ?>
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        </li>
                                                        <?php
                                                    }
                                                    if ( $team['contactemail'] ) {
                                                        ?>
                                                        <li class="list__item">
                                                            <a href="mailto:<?php echo esc_attr( $team['contactemail'] ); ?>" class="nav--link">
                                                                <svg width="16" height="16" class="icon ">
                                                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
                                                                </svg>
                                                                <span class="nav--link">
                                                                    <span class="nav-link__value">
                                                                        <?php echo esc_html( $team['contactemail'] ); ?>
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        </li>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </ul>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>
<?php
}
?>
