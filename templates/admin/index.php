<?php
/**
 * Index administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Util\Util_Lookup;

/** @var ?Index_Page_View_Model $vm */
if ( isset( $vm ) && $vm instanceof Index_Page_View_Model ) {
    $errors           = $vm->errors;
    $competition_name = $vm->competition_name;
    $type             = $vm->type;
    $age_group        = $vm->age_group;
}

if ( ! isset( $errors ) || ! ( $errors instanceof Error_Bag ) ) {
    $errors = new Error_Bag();
}

$is_invalid_class = defined( 'RACKETMANAGER_IS_INVALID' ) ? RACKETMANAGER_IS_INVALID : 'is-invalid';
?>
<div class="container">
    <h1><?php esc_html_e( 'Racketmanager Competitions', 'racketmanager' ); ?></h1>
    <div id="competitions-table" class="league-block-container mb-3">
                <div class="">
                    <?php
                    $competition_type = null;
                    $selected_competition_age_group = $age_group ?? null;
                    include __DIR__ . '/includes/competitions.php';
            ?>
        </div>
    </div>
    <div class="">
        <h3><?php esc_html_e( 'Add Competition', 'racketmanager' ); ?></h3>
        <!-- Add New Competition -->
        <form action="" method="post" class="form-control">
            <?php wp_nonce_field( 'racketmanager_add-competition', 'racketmanager_nonce' ); ?>
            <div class="row gx-3 mb-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <?php
                        $is_invalid = $errors->has( 'competition' );
                        $msg        = $errors->message( 'competition' );
                        ?>
                        <input class="form-control <?php echo $is_invalid ? esc_attr( $is_invalid_class ) : ''; ?>" placeholder="<?php esc_html_e( 'Enter name for new competition', 'racketmanager' ); ?>" type="text" name="competition_name" id="competition_name" value="<?php echo esc_attr( $competition_name ?? '' ); ?>" size="30" />
                        <label for="competition_name"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
                        <?php
                        if ( ! empty( $is_invalid ) ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row gx-3 mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = $errors->has( 'type' );
                        $msg        = $errors->message( 'type' );
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_attr( $is_invalid_class ) : ''; ?>" name="type" id="type">
                            <option disabled <?php selected( empty( $type ) ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( Util_Lookup::get_competition_types() as $competition_type_key => $competition_type_desc ) {
                                ?>
                                <option value="<?php echo esc_attr( $competition_type_key ); ?>" <?php selected( ( $type ?? '' ) === $competition_type_key ); ?>><?php echo esc_html( ucfirst( $competition_type_desc ) ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
                        <?php
                        if ( ! empty( $is_invalid ) ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <?php
                        $is_invalid = $errors->has( 'age_group' );
                        $msg        = $errors->message( 'age_group' );
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_attr( $is_invalid_class ) : ''; ?>" name="age_group" id="age_group">
                            <option disabled <?php selected( empty( $selected_competition_age_group ) ); ?>><?php esc_html_e( 'Select age group', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( Util_Lookup::get_age_groups() as $age_group_key => $age_group_desc ) {
                                ?>
                                <option value="<?php echo esc_attr( $age_group_key ); ?>" <?php selected( ( $selected_competition_age_group ?? '' ) === $age_group_key ); ?>><?php echo esc_html( $age_group_desc ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="age_group"><?php esc_html_e( 'Age Group', 'racketmanager' ); ?></label>
                        <?php
                        if ( ! empty( $is_invalid ) ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                         }
                        ?>
                    </div>
                </div>
            </div>
            <input type="hidden" name="addCompetition" value="competition" />
            <input type="submit" name="addCompetition" value="<?php esc_html_e( 'Add Competition', 'racketmanager' ); ?>" class="btn btn-primary" />

        </form>

    </div>
</div>
