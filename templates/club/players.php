<?php
/**
 *
 * Template page to players or a player for a club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 */

namespace Racketmanager;

global $racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $club */
/** @var bool   $user_can_update */
/** @var array  $players */
$display_opt = $racketmanager->get_options( 'display' );
if ( empty( $player ) ) {
    $header_level = 1;
    require_once RACKETMANAGER_PATH . 'templates/includes/club-header.php';
    if ( $user_can_update ) {
        ?>
        <div class="module module--card">
            <div class="module__banner">
                <a data-bs-toggle="collapse" href="#addPlayer" aria-expanded="false" aria-controls="addPlayer">
                    <h3 class="module__title"><?php esc_html_e( 'Add player', 'racketmanager' ); ?></h3>
                </a>
            </div>
            <div class="module__content collapse" id="addPlayer">
                <div class="module-container">
                    <form id="playerRequestFrm" action="" method="post">
                        <?php wp_nonce_field( 'club-player-request', 'racketmanager_nonce' ); ?>
                        <input type="hidden" name="club" id="club" value="<?php echo esc_html( $club->id ); ?>" />
                        <div class="form-floating mb-3">
                            <input required="required" type="text" class="form-control" id="firstname" name="firstname" size="30" placeholder="First name" aria-describedby="firstnameFeedback" />
                            <label for="firstname"><?php esc_html_e( 'First name', 'racketmanager' ); ?></label>
                            <div id="firstnameFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="form-floating mb-3">
                            <input required="required" type="text" class="form-control" id="surname" name="surname" size="30" placeholder="Surname" aria-describedby="surnameFeedback" />
                            <label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
                            <div id="surnameFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="form-floating mb-3">
                            <fieldset>
                                <legend id="gender"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
                                <div class="form-check">
                                    <input required="required" type="radio" id="genderMale" name="gender" value="M" class="form-check-input" />
                                    <label for="genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="genderFemale" name="gender" value="F" class="form-check-input" />
                                    <label for="genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
                                </div>
                                <div id="genderFeedback" class="invalid-feedback"></div>
                            </fieldset>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" placeholder="<?php esc_html_e( 'Enter LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" aria-describedby="btmFeedback" />
                            <label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
                            <div id="btmFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" name="year_of_birth" id="year_of_birth" aria-describedby="year_of_birthFeedback">
                                <option value=""><?php esc_html_e( 'Enter year of birth', 'racketmanager' ); ?></option>
                                <?php
                                $current_year = gmdate( 'Y' );
                                $start_year   = $current_year - 5;
                                $end_year     = $start_year - 100;
                                for ( $i = $start_year; $i > $end_year; $i-- ) {
                                    ?>
                                    <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'racketmanager' ); ?></label>
                            <div id="year_of_birthFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" placeholder="<?php esc_html_e( 'Enter email address', 'racketmanager' ); ?>" name="email" id="email" aria-describedby="emailFeedback" autocomplete="off" />
                            <label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
                            <div id="emailFeedback" class="invalid-feedback"></div>
                        </div>
                        <button class="btn mb-3" type="button" id="clubPlayerUpdateSubmit" data-action="club-player-request"><?php esc_html_e( 'Add player', 'racketmanager' ); ?></button>
                        <div id="playerAddResponse" class="alert_rm" style="display: none;">
                            <div class="alert__body">
                                <div class="alert__body-inner">
                                    <span id="playerAddResponseText"></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    $player_genders[ __( 'Female players', 'racketmanager' ) ] = 'F';
    $player_genders[ __( 'Male players', 'racketmanager' ) ]   = 'M';
    foreach ( $player_genders as $key => $gender ) {
        ?>
        <div class="module module--card">
            <div class="module__banner">
                <h3 class="module__title"><?php echo esc_html( $key ); ?></h3>
            </div>
            <div class="module__content">
                <div class="module-container">
                    <?php
                    if ( $players ) {
                        ?>
                        <div id="playerDel<?php echo esc_attr( $gender ); ?>Response" class="alert_rm" style="display: none;">
                            <div class="alert__body">
                                <div class="alert__body-inner">
                                    <span id="playerDel<?php echo esc_attr( $gender ); ?>ResponseText"></span>
                                </div>
                            </div>
                        </div>
                        <form id="club-player-<?php echo esc_html( $gender ); ?>-remove" method="post" action="">
                            <?php wp_nonce_field( 'club-player-remove', 'racketmanager_nonce' ); ?>
                            <table class="table table-striped table-borderless" aria-describedby="<?php echo esc_html( $club->name . ' ' . $key ); ?> Players">
                                <thead>
                                    <tr>
                                        <th scope="col" class="check-column">
                                            <?php
                                            if ( $user_can_update ) {
                                                ?>
                                                <button class="btn" type="button" id="clubPlayerRemoveSubmit" data-action="club-player-remove" data-form-id="club-player-<?php echo esc_html( $gender ); ?>-remove" data-gender="<?php echo esc_html( $gender ); ?>">
                                                    <?php esc_html_e( 'Remove', 'racketmanager' ); ?>
                                                </button>
                                                <?php
                                            }
                                            ?>
                                        </th>
                                        <th scope="col"><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                                        <th scope="col" class="colspan"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></th>
                                        <th scope="col" class="colspan">
                                            <?php
                                            if ( empty( $display_opt['wtn'] ) ) {
                                                esc_html_e( 'Rating Points', 'racketmanager' );
                                            } else {
                                                esc_html_e( 'WTN', 'racketmanager' );
                                            }
                                            ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="Club <?php echo esc_html( $key ); ?> Players">
                                    <?php $class = ''; ?>
                                    <?php
                                    foreach ( $players as $club_player ) {
                                        if ( $club_player->gender === $gender ) {
                                            $class = ( 'alternate' === $class ) ? '' : 'alternate';
                                            ?>
                                            <tr class="<?php echo esc_html( $class ); ?>" id="club_player-<?php echo esc_html( $club_player->registration_id ); ?>">
                                                <th scope="row" class="check-column">
                                                    <?php
                                                    if ( $user_can_update ) {
                                                        ?>
                                                        <label for="clubPlayer-<?php echo esc_html( $club_player->registration_id ); ?>" class="visually-hidden"><?php ?><?php esc_html_e( 'Check', 'racketmanager' ); ?></label>
                                                        <input type="checkbox" class="checkbox" value="<?php echo esc_html( $club_player->registration_id ); ?>" name="clubPlayer[<?php echo esc_html( $club_player->registration_id ); ?>]" id="clubPlayer-<?php echo esc_html( $club_player->registration_id ); ?>" />
                                                        <?php
                                                    }
                                                    ?>
                                                </th>
                                                <td><a href="<?php echo esc_html( seo_url( $club_player->display_name ) ); ?>/"><?php echo esc_html( $club_player->display_name ); ?></a></td>
                                                <td><?php echo esc_html( $club_player->btm ); ?></td>
                                                <td>
                                                    <?php
                                                    if ( empty( $display_opt['wtn'] ) ) {
                                                        $rating = $club_player->rating;
                                                    } else {
                                                        $rating = $club_player->wtn;
                                                    }
                                                    $match_types    = Util_Lookup::get_match_types();
                                                    $rating_display = '';
                                                    foreach ( $match_types as $match_type => $description ) {
                                                        if ( ! empty( $rating_display ) ) {
                                                            $rating_display .= ' - ';
                                                        }
                                                        $rating_display .= '[' . $match_type . ':' . $rating[ $match_type ] . ']';
                                                    }
                                                    echo ' ' . esc_html( $rating_display );
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </form>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    require_once RACKETMANAGER_PATH . 'templates/club/player.php';
}
