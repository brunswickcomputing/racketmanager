<?php
/**
 * Template for league selections
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var int|string $post_id */
/** @var string     $form_action */
/** @var array      $leagues */
/** @var array      $league_options */
/** @var object     $league */

$action_url     = $form_action ?? '';
$options_to_use = $league_options ?? [];
?>
<div id="racketmanager_archive_selections" class="mt-3">
    <form method="get" action="<?php echo esc_url( $action_url ); ?>" id="racketmanager_archive">
        <div class="row g-1 align-items-center">
            <input type="hidden" name="page_id" value="<?php echo esc_attr( (string) ( $post_id ?? '' ) ); ?>" />
            <div class="form-floating col-auto">
                <select class="form-select" size="1" name="league_id" id="league_id">
                    <?php foreach ( $options_to_use as $opt ) : ?>
                        <option value="<?php echo esc_attr( $opt['slug'] ); ?>"<?php echo ! empty( $opt['selected'] ) ? ' selected="selected"' : ''; ?>>
                            <?php echo esc_html( $opt['title'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
            </div>
        </div>
    </form>
</div>
