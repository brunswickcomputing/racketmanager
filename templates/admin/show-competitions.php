<?php
/**
 * Competitions main page administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;

/** @var Competitions_Page_View_Model $vm */
if ( isset( $vm ) && $vm instanceof Competitions_Page_View_Model ) {
    $page_title = $vm->page_title;
    $tournament = $vm->tournament;
}
?>
<div class="container mb-3">
    <h1><?php echo esc_html( $page_title ); ?></h1>
    <?php require_once 'includes/competitions.php'; ?>
    <?php
    if ( isset( $tournament ) ) {
        ?>
        <div class="mt-3">
            <a class="btn btn-secondary" href="<?php echo esc_url( manage_url( 'competitions', 'competitions', [ 'season' => (string) $tournament->season, 'type' => 'tournament', 'tournamenttype' => (string) $tournament->type, 'tournament' => (string) $tournament->id ] ) ); ?>"><?php esc_html_e( 'Add Competitions', 'racketmanager' ); ?></a>
        </div>
        <?php
    }
    ?>
</div>
