<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\League_Standings_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;

/** @var League_Standings_Page_View_Model|Tournament_Draw_Page_View_Model|Cup_Draw_Page_View_Model|\stdClass $vm */
$tab   = $vm->tab ?? $tab ?? ( is_array( $vars ?? null ) ? ( $vars['tab'] ?? '' ) : '' );
$group = $vm->group ?? $group ?? ( is_array( $vars ?? null ) ? ( $vars['group'] ?? '' ) : '' );
/** @var string $tab */
/** @var string $group */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>

<div class="container">
    <?php require RACKETMANAGER_PATH . 'templates/admin/includes/championship-tabs.php'; ?>
</div>
