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
    $event_seasons = $league->event->get_seasons_array();
    foreach ( array_reverse( $event_seasons ) as $season_entry ) {
        $opt_name = $season_entry['name'] ?? '';
        if ( $opt_name ) {
            ?>
            <option value=<?php echo esc_html( $opt_name ); ?> <?php selected( $season ?? '', $opt_name, false ); ?>><?php echo esc_html( $opt_name ); ?></option>
            <?php
        }
    }
    ?>
</select>
<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
