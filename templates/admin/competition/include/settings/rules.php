<?php
/**
 * Competition Settings availability administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array $rules_options */
ksort( $rules_options );
?>
<div class="form-control">
    <?php
    $is_submitted = ! empty( $_POST );
    foreach ( $rules_options as $option => $value ) {
        if ( $is_submitted ) {
            // If submitted, it's checked ONLY if it exists in the POST array
            $is_checked = isset( $_POST['rules'][ $option ] );
        } else {
            // If not submitted (first load), use the saved object data
            $is_checked = ! empty( $competition->config->rules[ $option ] );
        }
        ?>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="rules[<?php echo esc_html( $option ); ?>]" id="rules-<?php echo esc_html( $option ); ?>" value="1" <?php checked( true, $is_checked ); ?> <?php echo empty( $value ) ? 'disabled' : null; ?> />
            <label class="form-check-label" for="rules-<?php echo esc_html( $option ); ?>"><?php echo esc_html( ucfirst( $option ) ); ?></label>
        </div>
        <?php
    }
    ?>
</div>
