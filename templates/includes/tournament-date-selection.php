<?php
/**
 * Template for tournament matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Tournament_Matches_View_Model;

/** @var Tournament_Matches_View_Model $vm */
?>
<div class="form-wrapper">
    <div class="col-12">
        <form id="tournament-match-date-form" action="">
            <input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo esc_attr( $vm->tournament_name ); ?>" />
            <label class="visually-hidden" for="match_date"><?php esc_html_e( 'Date', 'racketmanager' ); ?></label><select class="form-select" name="match_date" id="match_date">
                <?php
                foreach ( $vm->match_dates as $tab ) {
                    ?>
                    <option value="<?php echo esc_attr( $tab->date ); ?>" <?php selected( $tab->is_selected ); ?>>
                        <?php echo esc_html( $tab->formatted_select ); ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </form>
    </div>
</div>
