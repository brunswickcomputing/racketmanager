<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

class Get_Member_Account_Form_Query {
    public function __construct(
        public readonly bool $is_logged_in = false,
        public readonly ?int $user_id = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        bool $is_logged_in = false,
        ?int $user_id = null
    ): self {
        return new self(
            is_logged_in: $is_logged_in,
            user_id: $user_id
        );
    }
}
