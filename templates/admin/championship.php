<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $tab */
/** @var string $group */
$page_param     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sub_page_param = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>

<div class="container">
    <?php
    if ( ! empty( $league->groups ) ) {
        ?>
        <div class="alignright" style="margin-right: 1em;">
            <form action="/wp-admin/admin.php" method="get" style="display: inline;">
                <input type="hidden" name="page" value="<?php echo esc_html( $page_param ); ?>" />
                <input type="hidden" name="subpage" value="<?php echo esc_html( $sub_page_param ); ?>" />
                <input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
                <label>
                    <select name="group" size="1">
                        <?php
                        foreach ( $league->championship->get_groups() as $key => $g ) {
                            ?>
                            <option value="<?php echo esc_html( $g ); ?>"<?php selected( $g, $group ); ?>>
                                <?php
                                /* translators: %s: group */
                                echo esc_html( sprintf( __( 'Group %s', 'racketmanager' ), $g ) );
                                ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </label>
                <input type="hidden" name="league-tab" value="<?php echo esc_html( $tab ); ?>" />
                <input type="submit" class="button-secondary" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
            </form>
        </div>
        <?php
    }
    ?>
    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/championship-tabs.php'; ?>
</div>
