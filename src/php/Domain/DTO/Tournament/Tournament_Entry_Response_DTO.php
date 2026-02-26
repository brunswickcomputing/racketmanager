<?php
/**
 * Tournament_Entry_Response_DTO API: Tournament_Entry_Response_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Entry Response Data Transfer Object
 */
readonly class Tournament_Entry_Response_DTO {

    /**
     * Tournament_Entry_Response_DTO constructor.
     *
     */
    public function __construct(
       public int $status,
       public string $message,
       public string $message_type = 'success',
       public ?string $return_link = null,
       public bool $payment_required = false,
    ) {}

}
