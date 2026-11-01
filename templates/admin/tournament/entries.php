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

$withdrawn_entries = $withdrawn_entries ?? $vars['withdrawn_entries'] ?? ( isset( $vm ) ? ( $vm->withdrawn_entries ?? array() ) : array() );
$unpaid_entries    = $unpaid_entries ?? $vars['unpaid_entries'] ?? ( isset( $vm ) ? ( $vm->unpaid_entries ?? array() ) : array() );
$pending_entries   = $pending_entries ?? $vars['pending_entries'] ?? ( isset( $vm ) ? ( $vm->pending_entries ?? array() ) : array() );
$confirmed_entries = $confirmed_entries ?? $vars['confirmed_entries'] ?? ( isset( $vm ) ? ( $vm->confirmed_entries ?? array() ) : array() );
$player_entry_urls = $player_entry_urls ?? $vars['player_entry_urls'] ?? ( isset( $vm ) ? ( $vm->player_entry_urls ?? array() ) : array() );
$renderer          = $renderer ?? $vars['renderer'] ?? null;
$player_list_base  = 'admin/tournament/player-list';

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
