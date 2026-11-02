<?php
/**
 * Template for team matches
 *
 * @package Racketmanager/Templates/Event
 */

namespace Racketmanager;

/** @var array $matches */
$matches = $vars['matches'] ?? $vm->matches ?? $matches ?? [];
if ( ! empty( $matches ) ) {
    ?>
    <select class="form-select" size="1" name="matchId" id="matchId" data-action="show-set-team-button">
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
