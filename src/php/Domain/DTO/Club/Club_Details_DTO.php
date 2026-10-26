<?php
declare( strict_types=1 );
/**
 * Club_Details_DTO API: Club_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Club;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Player\Player;

/**
 * Class to implement the Club Details Data Transfer Object
 */
class Club_Details_DTO {
    public ?int $id = null;
    public ?string $name = null;
    public ?string $shortcode = null;
    public ?string $type = null;
    public ?string $website = null;
    public ?string $contact_no = null;
    public ?string $founded = null;
    public ?string $facilities = null;
    public ?string $address = null;
    public ?Player $match_secretary = null;
    public array $roles = array();
    public ?bool $single = null;

    /**
     * Club_Details_DTO constructor.
     *
     * @param Club $club
     * @param array $roles
     * @param Player|null $match_secretary
     */
    public function __construct( Club $club, array $roles = array(), ?Player $match_secretary = null ) {
        $this->id              = $club->get_id();
        $this->name            = $club->get_name();
        $this->shortcode       = $club->get_shortcode();
        $this->type            = $club->get_type();
        $this->website         = $club->get_website();
        $this->contact_no      = $club->get_contact_no();
        $this->founded         = $club->get_founded();
        $this->facilities      = $club->get_facilities();
        $this->address         = $club->get_address();
        $this->match_secretary = $match_secretary;
        $this->roles           = $roles;
    }

}
