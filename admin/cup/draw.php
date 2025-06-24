<?php
/**
 * Cup draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string $tab */
/** @var object $competition */
/** @var string $season */
/** @var object $league */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <?php echo esc_html( $league->title ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $league->title ); ?> - <?php echo esc_html( $competition->name ); ?> - <?php echo esc_html( $season ); ?></h1>
    <?php require_once RACKETMANAGER_PATH . 'admin/includes/championship-tabs.php'; ?>
</div>
