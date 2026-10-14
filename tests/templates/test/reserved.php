<?php
echo 'renderer_ok:' . ( ( isset( $renderer ) && $renderer instanceof \Racketmanager\Services\View\View_Renderer_Interface ) ? 'yes' : 'no' );
echo '|vm_type:' . ( isset( $vm ) ? ( is_object( $vm ) ? get_class( $vm ) : gettype( $vm ) ) : 'unset' );
echo '|vars_type:' . ( isset( $vars ) ? gettype( $vars ) : 'unset' );
echo '|vars_count:' . ( ( isset( $vars ) && is_array( $vars ) ) ? count( $vars ) : 0 );
echo '|custom:' . ( $custom ?? 'none' );
