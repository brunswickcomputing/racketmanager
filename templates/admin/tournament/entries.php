<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Services\View\View_Renderer_Interface;

// Preferred input: $vm from the overview page.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Overview_Page_View_Model ) ? $vm : null;

// BC fallback: allow legacy locals if $vm isn't provided.
if ( $vm ) {
    $withdrawn_entries = $vm->withdrawn_entries;
    $unpaid_entries    = $vm->unpaid_entries;
    $pending_entries   = $vm->pending_entries;
    $confirmed_entries = $vm->confirmed_entries;
    $player_entry_urls = $vm->player_entry_urls;
}
$player_list_base  = 'admin/tournament/player-list';
$player_entry_urls = $player_entry_urls ?? array();

/** @var array $withdrawn_entries */
/** @var array $unpaid_entries */
/** @var array $pending_entries */
/** @var array $confirmed_entries */
/** @var View_Renderer_Interface $renderer */
?>
<div class="row">
    <div class="col-12 col-md-6">
        <?php
        if ( ! empty( $withdrawn_entries ) ) {
            ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Withdrawn Entries', 'racketmanager' ); ?> <?php echo '(' . array_sum( array_map( "count", $withdrawn_entries ) ) . ')'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $renderer->render( $player_list_base, $vm, [ 'player_list' => $withdrawn_entries, 'player_entry_urls' => $player_entry_urls, 'entered' => true ] ); ?>
                </tbody>
            </table>
            <?php
        }
        if ( ! empty( $unpaid_entries ) ) {
            ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Unpaid Entries', 'racketmanager' ); ?> <?php echo '(' . array_sum( array_map( "count", $unpaid_entries ) ) . ')'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $renderer->render( $player_list_base, $vm, [ 'player_list' => $unpaid_entries, 'player_entry_urls' => $player_entry_urls, 'entered' => true ] ); ?>
                </tbody>
            </table>
            <?php
        }
        ?>
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th><?php esc_html_e( 'Pending Entries', 'racketmanager' ); ?> <?php echo empty( $pending_entries ) ? null : '(' . array_sum( array_map( "count", $pending_entries ) ) . ')'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $renderer->render( $player_list_base, $vm, [ 'player_list' => $pending_entries, 'player_entry_urls' => $player_entry_urls, 'entered' => false ] ); ?>
            </tbody>
        </table>
    </div>
    <div class="col-12 col-md-6">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th><?php esc_html_e( 'Confirmed Entries', 'racketmanager' ); ?> <?php echo empty( $confirmed_entries ) ? null : '(' . array_sum( array_map( "count", $confirmed_entries ) ) . ')'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $renderer->render( $player_list_base, $vm, [ 'player_list' => $confirmed_entries, 'player_entry_urls' => $player_entry_urls, 'entered' => true ] ); ?>
            </tbody>
        </table>
    </div>
</div>
