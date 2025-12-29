<?php
/**
 * Competition Settings availability administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array $clubs */
?>
<div class="form-control">
    <table class="table table-striped align-middle" aria-describedby="<?php esc_html_e( 'Court availability', 'racketmanager' ); ?>">
        <thead class="table-dark">
            <tr>
                <th scope="row"><?php esc_html_e( 'Club', 'racketmanager' ); ?></th>
                <th scope="row"><?php esc_html_e( 'Number of Courts', 'racketmanager' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $clubs as $club ) {
                $club_id       = $club->get_id();
                $current_value = $_POST['num_courts_available'][ $club_id ] ?? $competition->config->num_courts_available[ $club_id ] ?? null;
                ?>
                <tr>
                    <td><?php echo esc_html( $club->name ); ?></td>
                    <td>
                        <label for="num_courts_available-[<?php echo esc_html( $club_id ); ?>]" class="visually-hidden"><?php esc_html_e( 'Number of courts', 'racketmanager' ); ?></label><input type="number" step="1" min="0" class="small-text" name="num_courts_available[<?php echo esc_html( $club_id ); ?>]" id="num_courts_available-<?php echo esc_html( $club->id ); ?>" value="<?php echo esc_html( $current_value ); ?>" />
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>
