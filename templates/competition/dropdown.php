<?php
/**
 * Template for event dropdown
 *
 * @package Racketmanager/Templates/Competition
 */

namespace Racketmanager;

/** @var array $events */
$events = $events ?? $vm->events ?? [];
?>
<select size='1' name='event_id' id='event_id' class="form-select" data-action="get-league-dropdown">
    <option value='0'><?php esc_html_e( 'Choose event', 'racketmanager' ); ?></option>
    <?php foreach ( $events as $event ) { ?>
        <option value=<?php echo esc_html( $event->id ); ?>><?php echo esc_html( $event->name ); ?></option>
        <?php
    }
    ?>
</select>
<label for="event_id"><?php esc_html_e( 'Event', 'racketmanager' ); ?></label>
