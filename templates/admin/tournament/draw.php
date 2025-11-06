<?php
/**
 * Tournament draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string $tab */
/** @var object $tournament */
/** @var object $league */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php echo esc_html( $league->title ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $league->title ); ?> - <?php echo esc_html( $tournament->name ); ?></h1>
    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/championship-tabs.php'; ?>
</div>
