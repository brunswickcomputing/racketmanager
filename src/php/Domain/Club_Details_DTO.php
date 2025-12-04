<?php
/**
 * Club_Details_DTO API: Club_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

/**
 * Class to implement the Club Details Data Transfer Object
 */
class Club_Details_DTO {
    public int $id;
    public string $name;
    public string $shortcode;
    public string $type;
    public ?string $website;
    public ?string $contact_no;
    public ?string $founded;
    public ?string $facilities;
    public string $address;
    public string $link;
    public ?Player $match_secretary;
    public array $roles;

    /**
     * Club_Details_DTO constructor.
     *
     * @param Club $club
     * @param array $roles
     * @param Player|null $match_secretary
     */
    public function __construct( Club $club, array $roles, ?Player $match_secretary ) {
        $this->id              = $club->get_id();
        $this->name            = $club->get_name();
        $this->shortcode       = $club->get_shortcode();
        $this->type            = $club->get_type();
        $this->website         = $club->get_website();
        $this->contact_no      = $club->get_contact_no();
        $this->founded         = $club->get_founded();
        $this->facilities      = $club->get_facilities();
        $this->address         = $club->get_address();
        $this->link            = $club->get_link();
        $this->match_secretary = $match_secretary;
        $this->roles           = $roles;
    }

}
