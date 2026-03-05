<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;

// Preferred input: $vm from the overview page.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Overview_Page_View_Model ) ? $vm : null;

// BC fallback: allow legacy locals if $vm isn't provided.
if ( $vm ) {
    $withdrawn_entries = $vm->withdrawn_entries;
    $unpaid_entries    = $vm->unpaid_entries;
    $pending_entries   = $vm->pending_entries;
    $confirmed_entries = $vm->confirmed_entries;
}

/** @var array $withdrawn_entries */
/** @var array $unpaid_entries */
/** @var array $pending_entries */
/** @var array $confirmed_entries */
?>
<?php
if ( ! empty( $withdrawn_entries ) ) {
    ?>
    <div class="row">
        <div class="col-12 col-md-6">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Withdrawn Entries', 'racketmanager' ); ?> <?php echo '(' . array_sum( array_map( "count", $withdrawn_entries ) ) . ')'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $player_list = $withdrawn_entries;
                    $entered     = true;
                    require 'player-list.php';
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
if ( ! empty( $unpaid_entries ) ) {
    ?>
    <div class="row">
        <div class="col-12 col-md-6">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Unpaid Entries', 'racketmanager' ); ?> <?php echo '(' . array_sum( array_map( "count", $unpaid_entries ) ) . ')'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $player_list = $unpaid_entries;
                    $entered     = true;
                    require 'player-list.php';
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>
<div class="row">
    <div class="col-12 col-md-6">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th><?php esc_html_e( 'Pending Entries', 'racketmanager' ); ?> <?php echo empty( $pending_entries ) ? null : '(' . array_sum( array_map( "count", $pending_entries ) ) . ')'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $player_list = $pending_entries;
                $entered     = false;
                require 'player-list.php';
                ?>
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
                <?php
                $player_list = $confirmed_entries;
                $entered     = true;
                require 'player-list.php';
                ?>
            </tbody>
        </table>
    </div>
</div>
