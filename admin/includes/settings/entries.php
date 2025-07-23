<?php
/**
 * Results administration panel
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$competition_types = Util::get_competition_types();
?>
<div class="container">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
        <?php
        $i = 0;
        foreach ( $competition_types as $competition_type ) {
            ?>
            <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo ( 0 === $i ) ? 'active' : null; ?>" id="entries-<?php echo esc_attr( $competition_type ); ?>-tab" data-bs-toggle="tab" data-bs-target="#entries-<?php echo esc_attr( $competition_type ); ?>" type="button" role="tab" aria-controls="entries-<?php echo esc_attr( $competition_type ); ?>" aria-selected="<?php echo ( 0 === $i ) ? 'true' : 'false'; ?>"><?php echo esc_html( ucfirst( $competition_type ) ); ?></button>
            </li>
            <?php
            ++$i;
        }
        ?>
    </ul>
    <!-- Tab panes -->
    <div class="tab-content">
    <?php
    $i = 0;
    foreach ( $competition_types as $competition_type ) {
        ?>
        <div id="entries-<?php echo esc_attr( $competition_type ); ?>" class="tab-pane fade <?php echo ( 0 === $i ) ? 'active show' : null; ?>" role="tabpanel" aria-labelledby="entries-<?php echo esc_attr( $competition_type ); ?>-tab">
            <div class="form-control">
                <div class="form-floating mb-3">
                    <select class="form-select" id="<?php echo esc_html( $competition_type ); ?>-entryLevel" name="<?php echo esc_html( $competition_type ); ?>[entryLevel]">
                        <option value="" disabled <?php selected( null, $options[$competition_type]['entry_level'] ?? null); ?>><?php esc_html_e( 'Choose level', 'racketmanager' ); ?></option>
                        <option value="secretary" <?php selected( 'secretary', $options[$competition_type]['entry_level'] ?? null); ?>><?php esc_html_e( 'Match secretary', 'racketmanager' ); ?></option>
                        <option value="captain" <?php selected( 'captain', $options[$competition_type]['entry_level'] ?? null); ?>><?php esc_html_e( 'Captain', 'racketmanager' ); ?></option>
                        <option value="player" <?php selected( 'player', $options[$competition_type]['entry_level'] ?? null); ?>><?php esc_html_e( 'Player', 'racketmanager' ); ?></option>
                    </select>
                    <label for="<?php echo esc_html( $competition_type ); ?>-entryLevel"><?php esc_html_e( 'Entry level', 'racketmanager' ); ?></label>
                </div>
            </div>
        </div>
        <?php
        ++$i;
    }
    ?>
    </div>
</div>
