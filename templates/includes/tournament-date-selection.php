<?php
/**
 * Template for tournament matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var string $current_match_date */
?>
<div class="form-wrapper">
    <div class="col-12">
        <form id="tournament-match-date-form" action="">
            <input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo esc_html( $tournament->name ); ?>" />
            <label class="visually-hidden" for="match_date"><?php esc_html__( 'Date', 'racketmanager') ?></label><select class="form-select" name="match_date" id="match_date">
                <?php
                foreach ( $tournament->match_dates as $match_date ) {
                    ?>
                    <option value="<?php echo esc_html( $match_date ); ?>" <?php selected( $current_match_date, $match_date ); ?>>
                        <?php echo esc_html( mysql2date( 'D j M', $match_date ) ); ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </form>
    </div>
</div>
