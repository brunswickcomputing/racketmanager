<?php
/**
 * Results_Checker API: Results_Checker class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Results_Checker
 */

namespace Racketmanager\Domain\Result;

use Racketmanager\Domain\DTOs\Results_Checker_Data;
use stdClass;

/**
 * Class to implement the result checker object
 */
class Results_Checker {
    /**
     * @var int
     */
    public int $id = 0;

    /**
     * @var int
     */
    public int $match_id = 0;

    /**
     * @var int
     */
    public int $team_id = 0;

    /**
     * @var int|null
     */
    public ?int $player_id = null;

    /**
     * @var int
     */
    public int $league_id = 0;

    /**
     * @var int|null
     */
    public ?int $rubber_id = null;

    /**
     * @var int|null
     */
    public ?int $status = null;

    /**
     * @var string
     */
    public string $description = '';

    /**
     * @var string|null
     */
    public ?string $updated_date = null;

    /**
     * @var int|null
     */
    public ?int $updated_user = null;

    /**
     * Results_Checker constructor.
     *
     * @param Results_Checker_Data|stdClass|null $data Data DTO or object.
     */
    public function __construct( Results_Checker_Data|stdClass|null $data = null ) {
        if ( ! $data ) {
            return;
        }

        $this->id                = $data->id ?? 0;
        $this->league_id         = $data->league_id ?? 0;
        $this->match_id          = $data->match_id ?? 0;
        $this->team_id           = $data->team_id ?? 0;
        $this->player_id         = $data->player_id ?? null;
        $this->rubber_id         = $data->rubber_id ?? null;
        $this->description       = $data->description ?? '';
        $this->status            = $data->status ?? null;
        $this->updated_date      = $data->updated_date ?? null;
        $this->updated_user      = $data->updated_user ?? null;
    }
}
