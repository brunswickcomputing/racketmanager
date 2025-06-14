<?php
/**
 * Template for season dropdown
 *
 * @package Racketmanager/Templates/League
 */

namespace Racketmanager;

/** @var object $league */
?>
<select size='1' name='season' id='season' class="form-select" onChange='Racketmanager.getMatchDropdown(<?php echo esc_html( $league->id ); ?>, this.value)'>
    <option value="0"><?php esc_html_e( 'Choose Season', 'racketmanager' ); ?></option>
    <?php
    foreach ( array_reverse( $league->event->seasons ) as $season_entry ) {
        ?>
        <option value=<?php echo esc_html( $season_entry['name'] ); ?> <?php selected( $season, $season_entry['name'], false ); ?>><?php echo esc_html( $season_entry['name'] ); ?></option>
        <?php
    }
    ?>
</select>
<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
