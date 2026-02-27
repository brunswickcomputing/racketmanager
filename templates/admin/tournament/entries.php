<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

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
                        <th><?php esc_html_e( 'Withdrawn Entries', 'racketmanager' ); ?> <?php echo '(' . count( $withdrawn_entries ) . ')'; ?></th>
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
                        <th><?php esc_html_e( 'Unpaid Entries', 'racketmanager' ); ?> <?php echo '(' . count( $unpaid_entries ) . ')'; ?></th>
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
                    <th><?php esc_html_e( 'Pending Entries', 'racketmanager' ); ?> <?php echo empty( $entries_pending ) ? null : '(' . count( $entries_pending ) . ')'; ?></th>
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
                    <th><?php esc_html_e( 'Confirmed Entries', 'racketmanager' ); ?> <?php echo empty( $entries_confirmed ) ? null : '(' . count( $entries_confirmed ) . ')'; ?></th>
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
