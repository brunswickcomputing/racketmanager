<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Update_Competition_Config_Command {
    public function __construct(
        public ?int $competition_id,
        public ?int $competition_id_passed,
        public object $config,
        public array $form_data,
    ) {
    }
}
