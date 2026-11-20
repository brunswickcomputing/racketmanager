<?php
/**
 * Event Settings championship administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;

/** @var Tournament_Event_Config_Page_View_Model|null $vm */
/** @var object $event */
/** @var array  $leagues */
/** @var string $error_tab */
/** @var bool   $is_invalid */
/** @var string $msg */
$event    = $event ?? $vm->event ?? null;
$leagues  = $leagues ?? $vm->leagues ?? array();
$tab_name = 'championship';
if ( ! isset( $event->config ) || ! is_object( $event->config ) ) {
    if ( $event instanceof \Racketmanager\Domain\Competition\Event ) {
        $event->set_config_object( new \stdClass() );
    } else {
        $event->config = new \stdClass();
    }
}
if ( ! isset( $event->config->primary_league ) ) {
    $event->config->primary_league = null;
}
?>
<div class="form-control">
    <div class="row">
        <div class="col-md-3">
            <?php
            $is_invalid = false;
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'primary_league', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'primary_league', $validator->err_flds, true );
                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
            }
            ?>
            <div class="form-floating mb-3">
                <select class="form-select" name="primary_league" id="primary_league">
                    <option value=""><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
                        <?php
                        foreach ( $leagues as $league ) {
                            ?>
                            <option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $event->config->primary_league, $league->id ); ?> ><?php echo esc_html( $league->name ?? $league->title ?? '' ); ?></option>
                            <?php
                        }
                        ?>
                </select>
                <label for='primary_league'><?php esc_html_e( 'Primary League', 'racketmanager' ); ?></label>
            </div>
            <?php
            if ( $is_invalid ) {
                ?>
                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
