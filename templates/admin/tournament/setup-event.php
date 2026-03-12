<?php
/**
 * Tournament draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Setup_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $match_dates = $vm->match_dates;
    $match_count = $vm->match_count;
    $tournament  = $vm->tournament;
    $season      = $vm->season;
    $league      = $vm->league;
    $validator   = $vm->validator;
}

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Setup_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $match_dates = $vm->match_dates;
    $match_count = $vm->match_count;
    $tournament  = $vm->tournament;
    $season      = $vm->season;
    $league      = $vm->league;
    $errors      = $vm->errors;
}

// Safety: ensure $errors always exists.
if ( ! isset( $errors ) || ! ( $errors instanceof Error_Bag ) ) {
    $errors = new Error_Bag();
}

/** @var array  $match_dates */
/** @var int    $match_count */
/** @var object $tournament */
/** @var string $season */
$num_match_dates = is_array( $match_dates ) ? count( $match_dates ) : 0;
if ( $num_match_dates ) {
    $match_date_index = $num_match_dates - 1;
} else {
    $match_date_index = null;
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
            if ( empty( $league ) ) {
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php esc_html_e( 'Setup', 'racketmanager' ); ?>
                <?php
            } else {
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a>  &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=draw&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>&amp;league=<?php echo esc_attr( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo; <?php esc_html_e( 'Event Setup', 'racketmanager' ); ?>
                <?php
            }
            ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Event Setup', 'racketmanager' ); ?> <?php echo empty( $league ) ? null : ' - ' . esc_html( $league->title ); ?> - <?php echo esc_html( $tournament->name ); ?></h1>
    <?php
    if ( ! empty( $league ) ) {
        ?>
        <h2><?php echo esc_html( $league->title ) . ' - ' . esc_html( $season ); ?></h2>
        <div class="row mb-3">
            <div class="col-4"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></div>
            <div class="col-auto"><?php echo esc_html( $league->num_teams_total ); ?></div>
        </div>
        <div class="row mb-3">
            <div class="col-4"><?php esc_html_e( 'Rounds', 'racketmanager' ); ?></div>
            <div class="col-auto"><?php echo esc_html( $league->championship->num_rounds ); ?></div>
        </div>
        <?php
    }
    ?>
    <form method="post" class="form-control mb-3">
        <?php wp_nonce_field( 'racketmanager_add_championship-fixtures', 'racketmanager_nonce' ); ?>
        <?php
        if ( empty( $league ) ) {
            ?>
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr( $tournament->id ); ?>" />
            <?php
        } else {
            ?>
            <input type="hidden" name="league_id" value="<?php echo esc_attr( $league->id ); ?>" />
            <?php
        }
        ?>
        <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
        <input type="hidden" name="action" value="<?php echo esc_attr( $match_action ); ?>" />
        <div class="row mb-3 fw-bold">
            <div class="col-4"><?php esc_html_e( 'Round', 'racketmanager' ); ?></div>
            <div class="col-4"><?php esc_html_e( 'Round Date', 'racketmanager' ); ?></div>
        </div>
        <?php
        $round = 0;
        if ( empty( $league ) ) {
            $object = $tournament;
        } else {
            $object = $league->championship;
        }
        foreach ( $object->finals as $final ) {
            if ( isset( $_POST['rounds'][ $round ]['match_date'] ) ) {
                $round_date = $_POST['rounds'][ $round ]['match_date'];
            } elseif( ! empty( $match_dates[ $match_date_index ] ) ) {
                $round_date = $match_dates[ $match_date_index ];
            } else {
                $round_date = '';
            }
            $field_id = 'rounds-' . $round . '-match_date';
            $is_invalid = $errors->has( $field_id );
            ?>
            <div class="row mb-3">
                <input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][key]" value="<?php echo esc_attr( $final['key'] ); ?>" />
                <input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][num_matches]" value="<?php echo esc_attr( $final['num_matches'] ); ?>" />
                <input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][round]" value="<?php echo esc_attr( $final['round'] ); ?>" />
                <div class="col-4"><?php echo esc_html( $final['name'] ); ?></div>
                <div class="col-4">
                    <label class="visually-hidden" for="<?php echo esc_attr( $field_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Round date', 'racketmanager' ); ?></label><input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" value="<?php echo esc_html( $round_date ); ?>" name="rounds[<?php echo esc_attr( $round ); ?>][match_date]" id="<?php echo esc_attr( $field_id ); ?>" />
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( $field_id ) ) ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
            --$match_date_index;
            ++$round;
        }
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
    if ( empty( $league ) ) {
        ?>
        <form method="post" class="mb-3">
            <?php wp_nonce_field( 'racketmanager_calculate_ratings', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr( $tournament->id ); ?>" />
            <input type="hidden" name="rank" value="calculate_rank" />
            <button class="btn btn-primary"><?php esc_html_e( 'Generate ratings', 'racketmanager' ); ?></button>
        </form>
        <?php
    }
    ?>

</div>
