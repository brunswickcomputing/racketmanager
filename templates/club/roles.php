<?php
/**
 *
 * Template page to show roles for a club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 */

namespace Racketmanager;

/** @var object $club */
$header_level = 1;
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Roles', 'racketmanager' ); ?></h3>
    </div>
    <div id="rolesResponse" class="alert_rm alert--danger" style="display: none;">
        <div class="alert__body">
            <div class="alert__body-inner">
                <span id="rolesResponseText"></span>
            </div>
        </div>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div class="module">
                <div class="row mb-2 row-header">
                    <div class="col-2 col-md-2">
                        <span><?php esc_html_e( 'Role', 'racketmanager' ); ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span><?php esc_html_e( 'User', 'racketmanager' ); ?></span>
                    </div>
                    <div class="col-6 col-md-3">
                        <span><?php esc_html_e( 'Email', 'racketmanager' ); ?></span>
                    </div>
                    <div class="col-6 col-md-2">
                        <span><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></span>
                    </div>
                </div>
                <?php
                foreach ( $club->roles as $role_type ) {
                    foreach ( $role_type as $role ) {
                        ?>
                        <div class="row mb-2 row-list">
                            <div class="col-2 col-md-2"><?php echo esc_html( $role->role->desc ); ?></div>
                            <div class="col-6 col-md-3">
                                <a href="user-modal"><span class="club-role" data-club-role-id="<?php echo esc_attr( $role->id ); ?>"><?php echo esc_html( $role->user->display_name ); ?></span></a>
                            </div>
                            <?php
                            if ( is_user_logged_in() ) {
                                ?>
                                <div class="col-6 col-md-3">
                                    <span class=""><?php echo empty( $role->user->email ) ? null : esc_html( $role->user->email ); ?></span>
                                </div>
                                <div class="col-6 col-md-2">
                                    <span class=""><?php echo empty( $role->user->contactno ) ? null : esc_html( $role->user->contactno ); ?></span>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php require RACKETMANAGER_PATH . 'templates/includes/modal-loading.php'; ?>
<div class="modal" id="clubRoleModal"></div>
<script type="text/javascript">
    const eventLinks = document.querySelectorAll('.club-role');
    eventLinks.forEach(el => el.addEventListener('click', function (e) {
        let clubRoleId = this.dataset.clubRoleId;
        Racketmanager.clubRoleModal(e, clubRoleId);
    }));
</script>
