<?php
/**
 * Template fragment for fixture result entry form
 *
 * @var Fixture_Detail_Read_Model $model
 */

use Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model;

?>
<div id="matchRubbers" class="row">
    <div class="page-content__sidebar col-12 col-lg-4">
        <div class="row">
            <div class="col-12">
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title">
                            <?php esc_html_e( 'How does it work?', 'racketmanager' ); ?>
                        </h3>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <h5 class="subheading">
                                <?php esc_html_e( 'Results', 'racketmanager' ); ?>
                            </h5>
                            <ul class="list list--naked small-txt">
                                <li class="list__item"><?php esc_html_e( "Only valid results are allowed. In the case of a non-played match, you can edit the status via the 'match status' button.", 'racketmanager' ); ?></li>
                                <li class="list__item"><?php esc_html_e( 'You can also mark a rubber as walkover, retired or not played.', 'racketmanager' ); ?></li>
                            </ul>
                        </div>
                        <div class="module-container">
                            <h5 class="subheading">
                                <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                            </h5>
                            <ul class="list list--naked small-txt">
                                <li class="list__item"><?php esc_html_e( 'You can add players to a match by choosing from the select list.', 'racketmanager' ); ?></li>
                                <li class="list__item"><?php esc_html_e( "When the player is not yet in the list, you can choose the '* Unregistered player' and add the name of the missing player in the comments.", 'racketmanager' ); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-content__main col-12 col-lg-8">
        <div class="module module--card">
            <div class="module__banner">
                <h3 class="module__title">
                    <?php esc_html_e( 'Rubber results', 'racketmanager' ); ?>
                </h3>
            </div>
            <div class="module__content">
                <div class="module-container">
                    <div id="viewMatchRubbers">
                        <div id="splash" class="container d-none">
                            <div class="module module--card">
                                <div class="spinner-loading d-flex justify-content-center">
                                    <output class="spinner-border">
                                        <span class="visually-hidden">Loading...</span>
                                    </output>
                                </div>
                            </div>
                        </div>
                        <div id="showMatchRubbers">
                            <form id="form-match-<?php echo esc_attr( $model->match_id ); ?>" class="team-match-result" method="post">
                                <?php wp_nonce_field( 'rubbers-match', 'racketmanager_nonce' ); ?>
                                <input type="hidden" name="updated_form" value="new"/>
                                <input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo esc_attr( $model->match_id ); ?>"/>
                                <input type="hidden" name="new_match_status" id="match_status" value="<?php echo esc_attr( $model->match_status ); ?>"/>
                                <div class="alert_rm" id="matchAlert" style="display:none;">
                                    <div class="alert__body">
                                        <div class="alert__body-inner" id="matchAlertResponse">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12 match__buttons">
                                        <?php
                                        $update_action = 'results';
                                        $button_text   = __( 'Save Result', 'racketmanager' );
                                        if ( $model->permissions['match_approval_mode'] ?? false ) {
                                            $update_action = 'confirm';
                                            $button_text   = __( 'Confirm Result', 'racketmanager' );
                                        }
                                        ?>
                                        <input type="hidden" name="updateRubber" id="updateRubber" value="<?php echo esc_attr( $update_action ); ?>"/>
                                        <a tabindex="999" class="btn btn-plain" href="<?php echo esc_url( $model->edit_url ); ?>"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></a>
                                        <button tabindex="500" class="btn btn-primary" type="button" id="updateRubberResults" data-action="update-team-result">
                                            <?php echo esc_html( $button_text ); ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="row mt-3 mb-3">
                                    <div class="form-floating">
                                        <textarea class="form-control result-comments" tabindex="490" placeholder="<?php esc_attr_e( 'Leave a comment here', 'racketmanager' ); ?>" name="matchComments[result]" id="matchComments"><?php echo esc_html( $model->general_comments ); ?></textarea>
                                        <label for="matchComments"><?php esc_html_e( 'Match Comments', 'racketmanager' ); ?></label>
                                    </div>
                                </div>

                                <?php
                                $show_approvals = false;
                                if ( ! empty( $model->approvals ) ) {
                                    foreach ( $model->approvals as $approval ) {
                                        if ( ! empty( $approval['approver_name'] ) ) {
                                            $show_approvals = true;
                                            break;
                                        }
                                    }
                                }

                                if ( $show_approvals ) {
                                    ?>
                                    <div class="mt-3" id="approvals">
                                        <div class="match is-editable">
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
                                                    foreach ( $model->approvals as $opponent_key => $approval ) {
                                                        ?>
                                                        <div class="match__row">
                                                            <div class="match__row-title">
                                                                <div class="match__row-title-header">
                                                                    <?php echo esc_html( $model->teams[ $opponent_key ]['team_name'] ); ?>
                                                                </div>
                                                                <div class="match__row-title-value">
                                                                    <?php
                                                                    if ( $approval['approver_name'] ) {
                                                                        ?>
                                                                        <span class="match__row-title-value-content"><span class="nav-link__value"><?php echo esc_html( $approval['approver_name'] ); ?></span></span>
                                                                        <?php
                                                                    } else {
                                                                        $user_type = $model->permissions['user_type'] ?? '';
                                                                        $user_team = $model->permissions['user_team'] ?? '';
                                                                        if ( 'admin' !== $user_type && ( $opponent_key === $user_team || 'both' === $user_team ) ) {
                                                                            ?>
                                                                            <div class="approval-check">
                                                                                <div class="form-check">
                                                                                    <input type="hidden" name="result_<?php echo esc_attr( $opponent_key ); ?>"/>
                                                                                    <input class="form-check-input" type="radio" name="resultConfirm" id="resultConfirm-<?php echo esc_attr( $opponent_key ); ?>" value="A" required/>
                                                                                    <label for="resultConfirm-<?php echo esc_attr( $opponent_key ); ?>" class="form-check-label"><?php esc_html_e( 'Confirm', 'racketmanager' ); ?></label>
                                                                                    <div id="resultConfirmFeedback" class="invalid-feedback"></div>
                                                                                </div>
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="radio" name="resultConfirm" id="resultChallenge-<?php echo esc_attr( $opponent_key ); ?>" value="C" required/>
                                                                                    <label for="resultChallenge-<?php echo esc_attr( $opponent_key ); ?>" class="form-check-label"><?php esc_html_e( 'Challenge', 'racketmanager' ); ?></label>
                                                                                    <div id="resultChallengeFeedback" class="invalid-feedback"></div>
                                                                                </div>
                                                                            </div>
                                                                            <?php
                                                                        }
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                            <?php
                                                            if ( $approval['approver_name'] ) {
                                                                if ( ( $model->permissions['match_update'] ?? false ) && 'admin' !== ( $model->permissions['user_type'] ?? '' ) ) {
                                                                    ?>
                                                                    <div class="match-comments form-floating">
                                                                        <textarea class="form-control result-comments" placeholder="<?php esc_attr_e( 'Leave a comment here', 'racketmanager' ); ?>" name="matchComments[<?php echo esc_attr( $opponent_key ); ?>]" id="matchComments-<?php echo esc_attr( $opponent_key ); ?>"><?php echo esc_html( $approval['comment'] ); ?></textarea>
                                                                        <label for="matchComments-<?php echo esc_attr( $opponent_key ); ?>"><?php esc_html_e( 'Comments', 'racketmanager' ); ?></label>
                                                                    </div>
                                                                    <?php
                                                                } elseif ( ! empty( $approval['comment'] ) ) {
                                                                    ?>
                                                                    <div class="match-comments">
                                                                        <span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $approval['comment'] ); ?></span>
                                                                    </div>
                                                                    <?php
                                                                }
                                                            } elseif ( 'admin' !== ( $model->permissions['user_type'] ?? '' ) && ( $opponent_key === ( $model->permissions['user_team'] ?? '' ) || 'both' === ( $model->permissions['user_team'] ?? '' ) ) ) {
                                                                ?>
                                                                <div class="match-comments form-floating">
                                                                    <textarea class="form-control result-comments" placeholder="<?php esc_attr_e( 'Leave a comment here', 'racketmanager' ); ?>" name="resultConfirmComments" id="resultConfirmComments-<?php echo esc_attr( $opponent_key ); ?>"></textarea>
                                                                    <label for="resultConfirmComments-<?php echo esc_attr( $opponent_key ); ?>"><?php esc_html_e( 'Challenge comments', 'racketmanager' ); ?></label>
                                                                    <div id="resultConfirmCommentsFeedback" class="invalid-feedback"></div>
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
                                    </div>
                                    <?php
                                }
                                ?>

                                <ul class="match-group">
                                    <?php
                                    $tab_index = 1;
                                    foreach ( $model->rubbers as $rubber ) {
                                        ?>
                                        <input type="hidden" name="id[<?php echo esc_attr( $rubber['number'] ); ?>]" value="<?php echo esc_html( $rubber['id'] ); ?>" />
                                        <input type="hidden" name="type[<?php echo esc_attr( $rubber['number'] ); ?>]" value="<?php echo esc_html( $rubber['type'] ); ?>" />
                                        <li class="match-group__item">
                                            <div class="match is-editable" id="rubber-<?php echo esc_attr( $rubber['id'] ); ?>">
                                                <div class="match__header">
                                                    <ul class="match__header-title">
                                                        <li class="match__header-title-item">
                                                            <span title="<?php echo esc_attr( $rubber['title'] ); ?>" class="nav--link">
                                                                <span class="nav-link__value"><?php echo esc_html( $rubber['title'] ); ?></span>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                    <?php
                                                    if ( is_user_logged_in() ) {
                                                        ?>
                                                        <div class="match__header-aside text-uppercase">
                                                            <div class="match__header-aside-block">
                                                                <a class="nav__link" role="button" data-action="open-rubber-status-modal" data-rubber-id="<?php echo esc_attr( $rubber['id'] ); ?>"
                                                                   data-rubber-number="<?php echo esc_attr( $rubber['number'] ); ?>" tabindex="<?php echo esc_attr( $tab_index++ ); ?>">
                                                                    <svg width="16" height="16" class="icon-plus nav-link__prefix">
                                                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#plus-lg' ); ?>"></use>
                                                                    </svg>
                                                                    <span class="nav-link__value"><?php esc_html_e( 'Score status', 'racketmanager' ); ?></span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="match__body">
                                                    <div class="match__row-wrapper">
                                                        <?php
                                                        foreach ( [ 'home', 'away' ] as $opponent_key ) {
                                                            $opponent = $rubber['opponents'][ $opponent_key ];
                                                            ?>
                                                            <div class="match__row">
                                                                <div class="match__row-title">
                                                                    <div class="match__row-title-header">
                                                                        <?php echo esc_html( $opponent['team_name'] ); ?>
                                                                    </div>
                                                                    <?php
                                                                    $players_to_render = $opponent['players'];
                                                                    if ( empty( $players_to_render ) ) {
                                                                        $num_players       = ( str_contains( $rubber['title'], 'D' ) || str_contains( $rubber['title'], 'X' ) ) ? 2 : 1;
                                                                        $players_to_render = array_fill( 0, $num_players, [ 'id' => 0, 'gender' => $opponent['gender'] ] );
                                                                    }
                                                                    foreach ( $players_to_render as $p_index => $player ) {
                                                                        $p_num          = $p_index + 1;
                                                                        $select_name    = "players[{$rubber['number']}][$opponent_key][$p_num]";
                                                                        $gender         = strtolower( $opponent['gender'] ) ?? 'm';
                                                                        $player_id_link = 'players_' . $rubber['number'] . '_' . $opponent_key . '_' . $p_num;
                                                                        ?>
                                                                        <div class="match__row-title-value">
                                                                            <span class="match__row-title-value-content">
                                                                                <span class="nav-link__value <?php echo $opponent['is_winner'] ? 'winner' : ''; ?>">
                                                                                    <select class="form-select" name="<?php echo esc_attr( $select_name ); ?>" id="<?php echo esc_attr( $player_id_link ); ?>" tabindex="<?php echo esc_attr( $tab_index++ ); ?>">
                                                                                        <option value="0">&nbsp;</option>
                                                                                        <?php
                                                                                        foreach ( $model->club_players[ $opponent_key ][ $gender ] ?? [] as $player_option ) {
                                                                                            $player_display = $player_option->get_fullname();
                                                                                            if ( $player_option->get_btm() ) {
                                                                                                $player_display .= ' - ' . $player_option->get_btm();
                                                                                            }
                                                                                            ?>
                                                                                            <option value="<?php echo esc_attr( $player_option->get_id() ); ?>" <?php selected( $player_option->get_id(), $player['id'] ); ?>>
                                                                                                <?php echo esc_html( $player_display ); ?>
                                                                                            </option>
                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                    <label class="visually-hidden" for="<?php echo esc_attr( $player_id_link ); ?>"><?php esc_html_e( 'Select player', 'racketmanager' ); ?></label>
                                                                                    <span id="<?php echo esc_attr( $player_id_link ); ?>Feedback" class="invalid-feedback"></span>

                                                                                </span>
                                                                            </span>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <span class="match__message <?php echo esc_attr( $opponent['message'] ? 'match-warning' : 'd-none' ); ?>"
                                                                      id="match-message-<?php echo esc_attr( $rubber['number'] ); ?>-<?php echo esc_attr( $opponent['team_id'] ?? '' ); ?>">
                                                                    <?php echo esc_html( $opponent['message'] ); ?>
                                                                </span>
                                                                <span class="match__status <?php echo esc_attr( $opponent['status_class'] ); ?>" id="match-status-<?php echo esc_attr( $rubber['number'] ); ?>-<?php echo esc_attr( $opponent['team_id'] ?? '' ); ?>"><?php echo esc_html( $opponent['status_text'] ); ?></span>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                        <label class="visually-hidden" for="match_status_<?php echo esc_attr( $rubber['number'] ); ?>"><?php esc_html_e( 'Rubber status', 'racketmanager'); ?></label>
                                                        <input type="text" class="d-none" id="match_status_<?php echo esc_attr( $rubber['number'] ); ?>" name="match_status[<?php echo esc_attr( $rubber['number'] ); ?>]" value="<?php echo esc_attr( $rubber['status_key'] ?? '' ); ?>"/>
                                                    </div>
                                                    <div class="match__result">
                                                        <?php
                                                        for ( $s_num = 1; $s_num <= $model->num_sets; $s_num++ ) {
                                                            $set            = $rubber['sets'][ $s_num ] ?? [];
                                                            $set_id         = "set_{$rubber['number']}_$s_num";
                                                            $home_val       = $set['home'] ?? '';
                                                            $away_val       = $set['away'] ?? '';
                                                            $home_won_class = ! empty( $set['home_won'] ) ? 'match-points__cell-input--won' : '';
                                                            $away_won_class = ! empty( $set['away_won'] ) ? 'match-points__cell-input--won' : '';
                                                            $tb_val         = $set['tiebreak'] ?? '';
                                                            $set_info       = $model->scoring_info[ $rubber['number'] ][ $s_num ] ?? [];
                                                            ?>
                                                            <ul class="match-points set-points" id="<?php echo esc_attr( $set_id ); ?>"
                                                                data-settype="<?php echo esc_attr( $set_info['set_type'] ?? '' ); ?>"
                                                                data-maxwin="<?php echo esc_attr( $set_info['max_win'] ?? 6 ); ?>"
                                                                data-maxloss="<?php echo esc_attr( $set_info['max_loss'] ?? 5 ); ?>"
                                                                data-minwin="<?php echo esc_attr( $set_info['min_win'] ?? 6 ); ?>"
                                                                data-minloss="<?php echo esc_attr( $set_info['min_loss'] ?? 0 ); ?>"
                                                                data-tiebreakset="<?php echo esc_attr( $set_info['tiebreak_set'] ?? 6 ); ?>">
                                                                <li class="match-points__cell">
                                                                    <label class="visually-hidden" for="<?php echo esc_attr( $set_id ); ?>_player1"><?php esc_html_e( 'Home points', 'racketmanager' ); ?></label>
                                                                    <input class="points match-points__cell-input <?php echo esc_attr( $home_won_class ); ?>" type="number" id="<?php echo esc_attr( $set_id ); ?>_player1" name="sets[<?php echo esc_attr( $rubber['number'] ); ?>][<?php echo esc_attr( $s_num ); ?>][player1]" value="<?php echo esc_attr( $home_val ); ?>" onblur="SetCalculator(this)" tabindex="<?php echo esc_attr( $tab_index++ ); ?>"/>
                                                                </li>
                                                                <li class="match-points__cell">
                                                                    <label class="visually-hidden" for="<?php echo esc_attr( $set_id ); ?>_player2"><?php esc_html_e( 'Away points', 'racketmanager' ); ?></label>
                                                                    <input class="points match-points__cell-input <?php echo esc_attr( $away_won_class ); ?>" type="number" id="<?php echo esc_attr( $set_id ); ?>_player2" name="sets[<?php echo esc_attr( $rubber['number'] ); ?>][<?php echo esc_attr( $s_num ); ?>][player2]" value="<?php echo esc_attr( $away_val ); ?>" onblur="SetCalculator(this)" tabindex="<?php echo esc_attr( $tab_index++ ); ?>"/>
                                                                </li>
                                                            </ul>
                                                            <div id="<?php echo esc_attr( $set_id ); ?>_tiebreak_wrapper" class="match-points set-points tie-break" <?php echo empty( $tb_val ) ? 'style="display:none;"' : ''; ?>>
                                                                <label class="visually-hidden" for="<?php echo esc_attr( $set_id ); ?>_tiebreak"><?php esc_html_e( 'Tie-break points', 'racketmanager' ); ?></label>
                                                                <input class="points match-points__cell-input" type="number" min="0" id="<?php echo esc_attr( $set_id ); ?>_tiebreak" name="sets[<?php echo esc_attr( $rubber['number'] ); ?>][<?php echo esc_attr( $s_num ); ?>][tiebreak]" value="<?php echo esc_attr( $tb_val ); ?>" onblur="SetCalculatorTieBreak(this)" tabindex="<?php echo esc_attr( $tab_index++ ); ?>"/>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="match__footer">
                                                    <ul class="match__footer-title">
                                                    </ul>
                                                    <div class="match__footer-aside text-uppercase">
                                                        <a role="button" data-action="reset-match-scores" data-rubber-id="<?php echo esc_attr( $rubber['id'] ); ?>" tabindex="<?php echo esc_attr( $tab_index++ ); ?>">
                                                            <?php esc_html_e( 'Reset scores', 'racketmanager' ); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
