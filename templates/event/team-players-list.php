<?php
/**
 * Template page to display team players for validation
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $club: club details
 *  $event: event details
 *  $club_players club players array
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var boolean $can_update */
/** @var array $teams */
/** @var object $event */
/** @var array $club_players */
/** @var object $club */
?>
<form id="team-order-validate">
    <?php
    if ( $can_update ) {
        ?>
        <div class="row gx-3 mb-3 align-items-center">
            <div class="form-floating col-auto">
                <select class="form-select" size="1" name="teamId" id="teamId">
                    <option value="" disabled selected><?php esc_html_e( 'Select team', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $teams as $team ) {
                        ?>
                        <option value="<?php echo esc_attr( $team->team_id ); ?>"><?php echo esc_html( $team->title ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="teamId"><?php esc_html_e( 'Team', 'racketmanager' ); ?></label>
            </div>
            <div class="form-floating col-auto" id="matches" style="display:none;"></div>
        </div>
        <?php
    }
    ?>
    <ul class="match-group" id="match">
    <?php
    for ( $i = 1; $i <= $event->num_rubbers; $i++ ) {
        $rubber_title = $event->type . $i;
        if ( 'D' === substr( $event->type, 1, 1 ) ) {
            $rubber_players = array(
                '1' => array(),
                '2' => array(),
            );
        } else {
            $rubber_players = array( '1' => array() );
        }
        if ( str_starts_with( $event->type, 'M' ) || str_starts_with( $event->type, 'B' ) ) {
            foreach ( $rubber_players as $p => $player ) {
                $rubber_players[ $p ]['gender'] = 'm';
            }
        } elseif ( str_starts_with( $event->type, 'W' ) || str_starts_with( $event->type, 'G' ) ) {
            foreach ( $rubber_players as $p => $player ) {
                $rubber_players[ $p ]['gender'] = 'f';
            }
        } elseif ( str_starts_with( $event->type, 'X' ) ) {
            $rubber_players['1']['gender'] = 'm';
            $rubber_players['2']['gender'] = 'f';
        }
        ?>
        <li class="match-group__item">
            <div class="match is-editable">
                <div class="match__header">
                    <ul class="match__header-title">
                        <li class="match__header-title-item">
                            <span title="<?php echo esc_attr( $rubber_title ); ?>" class="nav--link">
                                <span class="nav-link__value"><?php echo esc_html( $rubber_title ); ?></span>
                                <input type="hidden" name="rubber_num[ <?php echo esc_attr( $i ); ?> ]" id="rubber_num-<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( $i ); ?>" />
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="match__body">
                    <div class="match__row-wrapper">
                        <div class="match__row">
                            <div class="match__row-title">
                                <?php
                                foreach ( $rubber_players as $player_number => $player ) {
                                    ?>
                                    <div class="match__row-title-value">
                                        <span class="match__row-title-value-content">
                                            <span class="nav-link__value">
                                                <?php
                                                $player_id_link = 'players_' . $i . '_' . $player_number;
                                                ?>
                                                <select class="form-select" name="players[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $player_number ); ?>]" id="<?php echo esc_attr( $player_id_link ); ?>">
                                                    <option value="0">&nbsp;</option>
                                                    <?php
                                                    foreach ( $club_players[ $player['gender'] ] as $player_option ) {
                                                        if ( ! empty( $player_option->removed_date ) ) {
                                                            $disabled = 'disabled';
                                                        } else {
                                                            $disabled = '';
                                                        }
                                                        $player_display = $player_option->fullname;
                                                        if ( ! empty( $player_option->btm ) ) {
                                                            $player_display .= ' - ' . $player_option->btm;
                                                        }
                                                        ?>
                                                        <option value="<?php echo esc_attr( $player_option->roster_id ); ?>"  <?php echo esc_html( $disabled ); ?>>
                                                            <?php echo esc_html( $player_display ); ?>
                                                        </option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                                <label class="visually-hidden" for="<?php echo esc_attr( $player_id_link ); ?>"><?php echo esc_html( sprintf( __( 'Player %s for rubber %s', 'racketmanager' ), $player_number, $i ) ); ?></label>
                                                <div id="<?php echo esc_attr( $player_id_link ); ?>Feedback" class="invalid-feedback"></div>
                                            </span>
                                        </span>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <span class="match__message <?php echo empty( $match_message_text ) ? 'd-none' : ''; ?>" id="match-message-<?php echo esc_attr( $i ); ?>"></span>
                            <span class="match__status" id="match-status-<?php echo esc_attr( $i ); ?>"></span>
                        </div>
                    </div>
                    <div class="match__result">
                        <div class="wtn-rating">
                            <label class="visually-hidden" for="wtn_<?php echo esc_html( $i ); ?>"><?php echo esc_html( sprintf( __( 'combined WTN for rubber %s', 'racketmanager' ), $i ) ) ?></label><input class="form-control" type="text" readonly id="wtn_<?php echo esc_html( $i ); ?>" name="wtn[<?php echo esc_html( $i ); ?>]" tabindex=-999 value="" />
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <?php
    }
    ?>
    </ul>
    <input type="hidden" name="clubId" value="<?php echo esc_attr( $club->id ); ?>" />
    <input type="hidden" name="eventId" value="<?php echo esc_attr( $event->id ); ?>" />
    <div class="match__buttons">
        <a class="me-auto" href="" id="resetMatchScore">
            <?php esc_html_e( 'Reset', 'racketmanager' ); ?>
        </a>
        <button class="btn btn-secondary me-3" data-set-team="true" style="display:none;" id="setTeamButton"><?php esc_html_e( 'Set players', 'racketmanager' ); ?></button>
        <button class="btn btn-primary" id="validateTeamButton"><?php esc_html_e( 'Validate players', 'racketmanager' ); ?></button>
    </div>
</form>
