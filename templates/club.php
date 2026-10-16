<?php
/**
 *
 * Template page to display single club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\View_Models\Club_View_Model;

/** @var Club_View_Model|null $vm */
/** @var boolean $standalone */
/** @var object $club */
/** @var boolean $user_can_update_club */
$club_presenter       = $club_presenter ?? new Club_Presenter();
$standalone           = $vm->standalone ?? ( ! empty( $standalone ) );
$user_can_update_club = $vm->user_can_update_club ?? ( ! empty( $user_can_update_club ) );
$google_maps_key      = $vm->google_maps_key ?? ( $google_maps_key ?? '' );
$map_url              = $vm->map_url ?? ( isset( $club ) ? $club_presenter->get_map_url( $club, $google_maps_key ) : null );
$subpage_links        = $vm->subpage_links ?? ( isset( $club ) ? $club_presenter->get_subpage_links( $club ) : [] );
$club_id              = $vm->id ?? ( $club->id ?? 0 );
$match_sec_name       = $vm->match_secretary_name ?? ( $club->match_secretary->display_name ?? null );
$match_sec_id         = $vm->match_secretary_id ?? ( $club->match_secretary->id ?? null );
$match_sec_email      = $vm->match_secretary_email ?? ( $club->match_secretary->email ?? null );
$match_sec_phone      = $vm->match_secretary_contact_no ?? ( $club->match_secretary->contactno ?? null );
$has_match_secretary  = ! empty( $match_sec_name ) || ! empty( $match_sec_email ) || ! empty( $match_sec_phone ) || ! empty( $club->match_secretary );
$club_phone           = $vm->contact_no ?? ( $club->contact_no ?? null );
$club_website         = $vm->website ?? ( $club->website ?? null );
$club_address         = $vm->address ?? ( $club->address ?? null );
$club_founded         = $vm->founded ?? ( $club->founded ?? null );
$club_facilities      = $vm->facilities ?? ( $club->facilities ?? null );
$invoices_url         = $subpage_links['invoices'] ?? ( isset( $club ) ? $club_presenter->get_link( $club, 'invoices' ) : ( isset( $vm ) ? '/clubs/' . ( $vm->slug ?: sanitize_title( $vm->shortcode ) ) . '/invoices/' : '' ) );
$players_url          = $subpage_links['players'] ?? ( isset( $club ) ? $club_presenter->get_link( $club, 'players' ) : ( isset( $vm ) ? '/clubs/' . ( $vm->slug ?: sanitize_title( $vm->shortcode ) ) . '/players/' : '' ) );
$roles_url            = $subpage_links['roles'] ?? ( isset( $club ) ? $club_presenter->get_link( $club, 'roles' ) : '' );
$competitions_url     = $subpage_links['competitions'] ?? ( isset( $club ) ? $club_presenter->get_link( $club, 'competitions' ) : '' );
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
?>
<div class="module module--card">
    <?php
    if ( $standalone ) {
        ?>
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'About', 'racketmanager' ); ?></h3>
        </div>
        <?php
    }
    ?>
    <div class="module__content">
        <div class="module-container">
            <div class="team">
                <form id="clubUpdateFrm" action="" method="post">
                    <?php wp_nonce_field( 'club-update', 'racketmanager_nonce' ); ?>
                    <input type="hidden" id="club_id" name="club_id" value="<?php echo esc_html( $club_id ); ?>" />
                    <?php
                    if ( $has_match_secretary || $user_can_update_club ) {
                        ?>
                        <div class="form-control mb-3">
                            <legend><?php esc_html_e( 'Match secretary details', 'racketmanager' ); ?></legend>
                            <?php
                            if ( $has_match_secretary || $user_can_update_club ) {
                                ?>
                                <div class="row g-3">
                                    <div class="mb-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="matchSecretaryName" name="matchSecretaryName" value="<?php echo empty( $match_sec_name ) ? null : esc_html( $match_sec_name ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                            <label for="matchSecretaryName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
                                            <input type="hidden" id="matchSecretaryId" name="matchSecretaryId" value="<?php echo empty( $match_sec_id ) ? null : esc_html( $match_sec_id ); ?>" />
                                            <div id="matchSecretaryNameFeedback" class="invalid-tooltip"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <?php
                                    if ( is_user_logged_in() ) {
                                        ?>
                                        <?php
                                        if ( ! empty( $match_sec_email ) || $user_can_update_club ) {
                                            ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-1">
                                                    <input type="email" class="form-control" id="matchSecretaryEmail" name="matchSecretaryEmail" value="<?php echo empty( $match_sec_email ) ? null : esc_html( $match_sec_email ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                                    <label for="matchSecretaryEmail"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
                                                    <div id="matchSecretaryEmailFeedback" class="invalid-tooltip"></div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if ( ! empty( $match_sec_phone ) || $user_can_update_club ) {
                                            ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-floating mb-1">
                                                    <input type="tel" class="form-control" id="matchSecretaryContactNo" name="matchSecretaryContactNo" value="<?php echo empty( $match_sec_phone ) ? null : esc_html( $match_sec_phone ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                                    <label for="matchSecretaryContactNo"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
                                                    <div id="matchSecretaryContactNoFeedback" class="invalid-tooltip"></div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="form-floating mb-3">
                                            <div class="contact-login-msg">You need to <a href="<?php echo esc_html( wp_login_url() ); ?>">login</a> to access match secretary contact details</div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                    <fieldset class="form-control mb-3">
                        <legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <?php
                                if ( $club_phone || $user_can_update_club ) {
                                    ?>
                                    <div class="form-floating mb-1">
                                        <input type="tel" class="form-control" id="clubContactNo" name="clubContactNo" value="<?php echo esc_html( $club_phone ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                        <label for="clubContactNo"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
                                        <div id="clubContactNoFeedback" class="invalid-tooltip"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <?php
                                if ( $club_website || $user_can_update_club ) {
                                    ?>
                                    <div class="form-floating mb-1">
                                        <input type="url" class="form-control" id="website" name="website" value="<?php echo esc_html( $club_website ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                        <label for="website"><?php esc_html_e( 'Website', 'racketmanager' ); ?></label>
                                        <div id="websiteFeedback" class="invalid-tooltip"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="mb-3">
                                <?php
                                if ( $club_address || $user_can_update_club ) {
                                    ?>
                                    <div class="form-floating mb-1">
                                        <input type="text" class="form-control" id="address" name="address" autocomplete="off" placeholder="<?php esc_html_e( 'Club address', 'racketmanager' ); ?>"  <?php disabled( $user_can_update_club, false ); ?> value="<?php echo esc_html( $club_address ); ?>" />
                                        <label for="address"><?php esc_html_e( 'Address', 'racketmanager' ); ?></label>
                                        <div id="addressFeedback" class="invalid-tooltip"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="form-control mb-3">
                        <legend><?php esc_html_e( 'Information', 'racketmanager' ); ?></legend>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <?php
                                if ( $club_founded || $user_can_update_club ) {
                                    ?>
                                    <div class="form-floating mb-1">
                                        <input type="number" class="form-control" id="founded" name="founded" min="1800" max="2099" step="1" value="<?php echo esc_html( $club_founded ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                        <label for="founded"><?php esc_html_e( 'Founded', 'racketmanager' ); ?></label>
                                        <div id="foundedFeedback" class="invalid-tooltip"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <?php
                                if ( $club_facilities || $user_can_update_club ) {
                                    ?>
                                    <div class="form-floating mb-1">
                                        <input type="text" class="form-control" id="facilities" name="facilities" value="<?php echo esc_html( $club_facilities ); ?>" <?php disabled( $user_can_update_club, false ); ?> />
                                        <label for="facilities"><?php esc_html_e( 'Facilities', 'racketmanager' ); ?></label>
                                        <div id="facilitiesFeedback" class="invalid-tooltip"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </fieldset>
                    <div id="club"></div>
                    <div id="clubFeedback" class="invalid-tooltip"></div>
                    <?php
                    if ( $user_can_update_club ) {
                        ?>
                        <button class="btn mb-3" type="button" id="updateClubSubmit" name="updateClubSubmit"><?php esc_html_e( 'Update details', 'racketmanager' ); ?></button>
                        <div class="updateResponse" id="updateClub"></div>
                        <div id="clubUpdateResponse" class="alert_rm" style="display: none;">
                            <div class="alert__body">
                                <div class="alert__body-inner">
                                    <span id="clubUpdateResponseText"></span>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
if ( $standalone && $club_id ) {
    latest_results( $club_id, array( 'header_level' => 3 ) );
}
?>
<?php
if ( $standalone ) {
    ?>
    <?php
    if ( $user_can_update_club && ! empty( $invoices_url ) ) {
        ?>
         <div class="module module--card">
             <div class="module__content">
                 <div class="module-container">
                     <a href="<?php echo esc_url( $invoices_url ); ?>">
                         <h3 class="module__title"><?php esc_html_e( 'Invoices', 'racketmanager' ); ?></h3>
                     </a>
                 </div>
             </div>
         </div>
        <?php
    }
    ?>
    <?php
    if ( ! empty( $players_url ) ) {
        ?>
        <div class="module module--card">
            <div class="module__content">
                <div class="module-container">
                    <a href="<?php echo esc_url( $players_url ); ?>">
                        <h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
    if ( ! empty( $roles_url ) ) {
        ?>
        <div class="module module--card">
            <div class="module__content">
                <div class="module-container">
                    <a href="<?php echo esc_url( $roles_url ); ?>">
                        <h3 class="module__title"><?php esc_html_e( 'Roles', 'racketmanager' ); ?></h3>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
    if ( ! empty( $competitions_url ) ) {
        ?>
        <div class="module module--card">
            <div class="module__content">
                <div class="module-container">
                    <a href="<?php echo esc_url( $competitions_url ); ?>">
                        <h3 class="module__title"><?php esc_html_e( 'Competitions', 'racketmanager' ); ?></h3>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
}
?>
<?php
if ( $standalone && ! empty( $map_url ) ) {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Map', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <fieldset class="club-address">
                    <legend class="d-none"><?php esc_html_e( 'Map', 'racketmanager' ); ?></legend>
                    <iframe title="<?php esc_html_e( 'Club map', 'racketmanager' ); ?>" width="100%" height="320" id="clubMap" name="clubMap" src="<?php echo esc_url( $map_url ); ?>" allowfullscreen></iframe>
                </fieldset>
            </div>
        </div>
    </div>
    <?php
}
?>
