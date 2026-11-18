<?php
/**
 * Tournament draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Application\Ports\View_Renderer_Interface;

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Draw_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $tab        = $vm->tab;
    $tournament = $vm->tournament;
    $league     = $vm->league;
    $season     = $vm->season;
}

/** @var string $tab */
/** @var object $tournament */
/** @var object $league */
/** @var string $season */
/** @var View_Renderer_Interface $renderer */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    Racketmanager.activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="<?php echo esc_url( manage_url( 'tournaments' ) ); ?>"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="<?php echo esc_url( manage_url( 'tournaments', 'tournament', [ 'tournament' => (string) $tournament->id, 'season' => (string) $tournament->season ] ) ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php echo esc_html( $league->name ?? $league->title ?? '' ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $league->name ?? $league->title ?? '' ); ?> - <?php echo esc_html( $tournament->name ); ?></h1>
    <?php $renderer->render( 'admin/includes/championship-tabs', $vm ); ?>
</div>
