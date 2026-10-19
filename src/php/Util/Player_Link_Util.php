<?php
declare( strict_types=1 );

namespace Racketmanager\Util;

class Player_Link_Util {
    public static function generate_link( string $display_name, ?string $btm = null ): string {
        $seo_name = Util::seo_url( $display_name );
        $link     = '/player/' . $seo_name . '/';
        if ( ! empty( $btm ) ) {
            $link .= $btm . '/';
        }
        return $link;
    }
}
