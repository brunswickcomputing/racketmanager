<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var array $withdrawn_entries */
/** @var array $pay_due_entries */
/** @var array $refund_due_entries */
/** @var array $pending_entries */
/** @var array $confirmed_entries */
?>
<?php
if ( ! empty( $entries_withdrawn ) ) {
    ?>
    <div class="row">
        <div class="col-12 col-md-6">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Withdrawn Entries', 'racketmanager' ); ?> <?php echo '(' . count( $entries_withdrawn ) . ')'; ?></th>
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
if ( ! empty( $entries_pay_due ) || ! empty( $entries_refund_due ) ) {
    ?>
    <div class="row">
        <?php
        if ( ! empty( $entries_pay_due ) ) {
            ?>
            <div class="col-12 col-md-6">
                <table class="table table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th class="d-flex justify-content-between align-items-center">
                            <span><?php esc_html_e( 'Unpaid Entries', 'racketmanager' ); ?> <?php echo '(' . count( $entries_pay_due ) . ')'; ?></span>
                            <button class="btn btn-secondary btn-sm notifyUnpaid" data-tournament-id="<?php echo esc_html( $tournament->id ); ?>"><?php esc_html_e( 'Notify unpaid', 'racketmanager' ); ?></button>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $player_list = $pay_due_entries;
                    $entered     = true;
                    require 'player-list.php';
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        if ( ! empty( $entries_refund_due ) ) {
            ?>
            <div class="col-12 col-md-6">
                <table class="table table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Refund Due Entries', 'racketmanager' ); ?> <?php echo '(' . count( $refund_due_entries ) . ')'; ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $player_list = $refund_due_entries;
                    $entered     = true;
                    require 'player-list.php';
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
?>
<div class="row">
    <div class="col-12 col-md-6">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="d-flex justify-content-between align-items-center">
                        <span><?php esc_html_e( 'Pending Entries', 'racketmanager' ); ?> <?php echo empty( $entries_pending ) ? null : '(' . count( $entries_pending ) . ')'; ?></span>
                        <?php if ( ! empty( $entries_pending ) ) : ?>
                            <button class="btn btn-secondary btn-sm notifyPending" data-tournament-id="<?php echo esc_html( $tournament->id ); ?>"><?php esc_html_e( 'Notify pending', 'racketmanager' ); ?></button>
                        <?php endif; ?>
                    </th>
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
