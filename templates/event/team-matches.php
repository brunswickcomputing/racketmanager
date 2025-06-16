<?php
/**
 * Template for team matches
 *
 * @package Racketmanager/Templates/Event
 */

namespace Racketmanager;

/** @var array $matches */
if ( $matches ) {
    ?>
    <select class="form-select" size="1" name="matchId" id="matchId" onChange="Racketmanager.show_set_team_button()">
        <option value="" disabled selected><?php esc_html_e( 'Select match', 'racketmanager' ); ?></option>
        <?php
        foreach ( $matches as $match ) {
            ?>
            <option value="<?php echo esc_attr( $match->id ); ?>"><?php echo esc_html( $match->match_title ); ?></option>
            <?php
        }
        ?>
    </select>
    <label for="matchId"><?php esc_html_e( 'Match', 'racketmanager' ); ?></label>
    <?php
}
