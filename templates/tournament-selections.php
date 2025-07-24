<?php
/**
 * Tournament selections template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var int $post_id */
/** @var array $tournaments */
/** @var object $tournament */
if ( empty( $selection_id ) ) {
    $selection_id = 'racketmanager_tournament';
}
?>
<div id="racketmanager_tournament_selections">
    <form method="get" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>" id="<?php echo esc_html( $selection_id ); ?>">
        <div class="row g-1 align-items-center">
            <input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
            <?php
            if ( ! empty( $season ) ) {
                ?>
                <input type="hidden" name="season" id="season" value="<?php echo esc_html( $season ); ?>" />
                <?php
            }
            ?>
            <div class="form-floating col-auto">
                <select class="form-select" size="1" name="tournament_id" id="tournament_id">
                    <?php
                    foreach ( $tournaments as $t ) {
                        ?>
                        <option value="<?php echo esc_html( $t->name ); ?>" <?php selected( $t->id, $tournament->id ); ?>>
                            <?php echo esc_html( $t->name ); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <label for="tournament_id"><?php echo esc_html( ucfirst( $tournament->competition->age_group ) ) . ' ' . esc_html__( 'Tournaments', 'racketmanager' ); ?></label>
            </div>
        </div>
    </form>
</div>
