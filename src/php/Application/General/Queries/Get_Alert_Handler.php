<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;

class Get_Alert_Handler {
    public function handle( Get_Alert_Query $query ): Shortcode_View_Model {
        $filename = ! empty( $query->template ) ? 'alert-' . $query->template : 'alert';

        return Shortcode_View_Model::for_template( $filename, [
            'msg'   => $query->msg,
            'class' => $query->type,
        ] );
    }
}
