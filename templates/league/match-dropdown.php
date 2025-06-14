<?php
/**
 * Template for match dropdown
 *
 * @package Racketmanager/Templates/League
 */

namespace Racketmanager;

/** @var array $matches */
?>
<select class="form-select" size="1" name="match_id" id="match_id" class="alignleft">
    <option value="0"><?php esc_html_e( 'Choose Match', 'racketmanager' ); ?></option>
    <?php
    foreach ( $matches as $match ) {
        ?>
        <option value="<?php echo esc_html( $match->id ); ?>" <?php echo selected( $match_id, $match->id, false ); ?>><?php echo esc_html( $match->get_title( false ) ); ?></option>
    <?php } ?>
</select>
<label for="match_id"><?php esc_html_e( 'Match', 'racketmanager' ); ?></label>
