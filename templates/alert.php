<?php
/**
 * Template for alert
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $msg */
/** @var string $class */
?>
<div>
    <div class="alert_rm alert--<?php echo esc_attr( $class ); ?>">
        <div class="alert__body">
            <div class="alert__body-inner">
                <span><?php echo $msg; ?></span>
            </div>
        </div>
    </div>
</div>
