<?php
/**
 * Template for league dropdown
 *
 * @package Racketmanager/Templates/Event
 */

namespace Racketmanager;

/** @var array $leagues */
?>
<select size='1' name='league_id' id='league_id' class="form-select" onChange='Racketmanager.getSeasonDropdown(this.value)'>
    <option value='0'><?php esc_html_e( 'Choose league', 'racketmanager' ); ?></option>
    <?php
    foreach ( $leagues as $league ) {
        ?>
        <option value=<?php echo esc_html( $league->id ); ?>><?php echo esc_html( $league->title ); ?></option>
        <?php
    }
    ?>
</select>
<label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
