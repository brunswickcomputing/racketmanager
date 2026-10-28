<?php
/**
 * Cup draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Setup_Page_View_Model;

/** @var Setup_Page_View_Model $vm */
$competition    = $vm->competition;
$season         = (string) $vm->season;
$current_season = (array) $vm->current_season;
$event          = $vm->event;
$league         = $vm->league;
$match_count    = $vm->match_count;
$match_dates = $current_season['match_dates'];
if ( $current_season['home_away'] ) {
    $round_rows       = $current_season['num_match_days'] / 2;
    $columns          = array( 0, 1 );
    $dates_array      = array_chunk( $match_dates, $round_rows );
    $titles           = array( __( 'First Half', 'racketmanager' ), __( 'Second Half', 'racketmanager' ) );
    $col_field        = 'col-md-6';
} else {
    $dates_array[] = $match_dates;
    $columns       = array( 0 );
    $titles        = array( __( 'Match dates', 'racketmanager' ) );
    $col_field     = 'col-12';
}
if ( empty( $league ) ) {
    $button_text  = __( 'Set round dates', 'racketmanager' );
    $match_action = null;
} elseif ( $match_count ) {
    $button_text  = __( 'Replace fixtures', 'racketmanager' );
    $match_action = 'replace';
} else {
    $button_text  = __( 'Add fixtures', 'racketmanager' );
    $match_action = 'add';
}
?>
<div class="container">
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <?php
            if ( ! empty( $event ) ) {
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s"><?php echo esc_html( ucfirst( $competition->get_type() ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=event&amp;event_id=<?php echo esc_attr( $event->id );  ?>&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $event->name ); ?></a> &raquo; <?php esc_html_e( 'Setup', 'racketmanager' ); ?>
                <?php
            } else {
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s"><?php echo esc_html( ucfirst( $competition->get_type() ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->get_type() ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <?php esc_html_e( 'Setup', 'racketmanager' ); ?>
                <?php
            }
            ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Setup', 'racketmanager' ); ?> - <?php echo esc_html( $competition->name ); ?> - <?php echo esc_html( $season ); ?></h1>
    <?php
    if ( ! empty( $event ) ) {
        ?>
        <h2><?php echo esc_html( $event->name ); ?></h2>
        <?php
    }
    ?>
    <form method="post" class="form-control mb-3">
        <?php wp_nonce_field( 'racketmanager_add_championship-fixtures', 'racketmanager_nonce' ); ?>
        <?php
        if ( ! empty( $event ) ) {
            ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
            <?php
        } else {
            ?>
            <input type="hidden" name="competition_id" value="<?php echo esc_attr( $competition->id ); ?>" />
            <?php
        }
        ?>
        <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
        <input type="hidden" name="action" value="<?php echo esc_attr( $match_action ); ?>" />
        <div class="row">
            <?php
            $round = 1;
            foreach ( $columns as $column ) {
                $row_title   = $titles[ $column ];
                $round_dates = $dates_array[ $column ];
                ?>
                <div class="<?php echo esc_attr( $col_field ); ?>">
                    <div class="row mb-3 ">
                        <div class="fw-bold"><?php echo esc_html( $row_title ); ?></div>
                    </div>
                    <?php
                    foreach ( $round_dates as $round_date ) {
                        ?>
                        <div class="row mb-3">
                            <input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][round]" value="<?php echo esc_attr( $round ); ?>" />
                            <div class="form-floating">
                                <input type="date" class="form-control" value="<?php echo esc_html( $round_date ); ?>" name="rounds[<?php echo esc_attr( $round ); ?>][match_date]" id="rounds-<?php echo esc_attr( $round ); ?>" />
                                <label for="rounds-<?php echo esc_attr( $round ); ?>" class="form-label"><?php /* translators: %s: round number */ printf( esc_html__( 'Match day %s', 'racketmanager' ), esc_html( $round ) ); ?></label>
                            </div>
                        </div>
                        <?php
                        ++$round;
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        if ( ! empty( $match_count ) ) {
            ?>
            <div class="alert_rm alert--info">
                <div class="alert__body">
                    <div class="alert__body-inner">
                        <span><?php esc_html_e( 'Existing fixtures will be replaced', 'racketmanager' ); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        <button class="btn btn-primary"><?php echo esc_html( $button_text ); ?></button>
    </form>
    <?php
    if ( empty( $event ) ) {
        ?>
        <form method="post" class="mb-3">
            <?php wp_nonce_field( 'racketmanager_calculate_ratings', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
            <input type="hidden" name="competition_id" value="<?php echo esc_attr( $competition->id ); ?>" />
            <input type="hidden" name="rank" value="calculate_ratings" />
            <button class="btn btn-primary"><?php esc_html_e( 'Generate ratings', 'racketmanager' ); ?></button>
        </form>
        <?php
    }
    ?>
</div>
