<?php
/**
 * Club Membership View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Club_Membership_View_Model {
    public function __construct(
        public string $club_name = '',
        public string $club_display_name = '',
        public ?string $approval_date = null,
        public string $approval_date_display = '',
        public ?string $removal_date = null,
        public string $removal_date_display = '',
        public ?string $registration_date = null,
        public string $registration_date_display = '',
        public ?int $club_id = null,
        public ?int $user_id = null,
        public ?int $registration_id = null,
        public ?int $approved_by_user_id = null,
        public ?int $registered_by_user_id = null,
        public ?int $removed_by_user_id = null,
        public ?bool $system_record = null,
        public string $club_url = '',
    ) {}
}
