<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Alert_Result;

class Get_Alert_Handler {
    public function handle( Get_Alert_Query $query ): Alert_Result {
        return new Alert_Result(
            msg: $query->msg,
            type: $query->type,
            template: $query->template
        );
    }
}
