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
    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/championship-tabs.php'; ?>
</div>
