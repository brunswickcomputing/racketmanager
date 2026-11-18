<?php
/**
 * Cup draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;

/** @var Cup_Draw_Page_View_Model $vm */
$competition = $vm->competition;
$league      = $vm->league;
$season      = $vm->season;
$tab         = $vm->tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$finals      = $vm->finals;

/** @var string $tab */
/** @var object $competition */
/** @var string $season */
/** @var object $league */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    Racketmanager.activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="<?php echo esc_url( manage_url( $competition->get_type() . 's' ) ); ?>"><?php echo esc_html( ucfirst( $competition->get_type() ) ); ?>s</a> &raquo; <a href="<?php echo esc_url( manage_url( $competition->get_type() . 's', 'seasons', [ 'competition_id' => (string) $competition->id ] ) ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="
            <?php
            echo esc_url(
                manage_url(
                    $competition->get_type() . 's',
                    'overview',
                    [
                        'competition_id' => (string) $competition->id,
                        'season'         => $season,
                    ]
                )
            );
            ?>
            "><?php echo esc_html( $season ); ?></a> &raquo; <?php echo esc_html( $league->name ?? $league->title ?? '' ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $league->name ?? $league->title ?? '' ); ?> - <?php echo esc_html( $competition->name ); ?> - <?php echo esc_html( $season ); ?></h1>
    <?php require RACKETMANAGER_PATH . 'templates/admin/includes/championship-tabs.php'; ?>
</div>
