<?php
declare( strict_types=1 );
/**
 * Contact Page View Model
 *
 * View model for admin contact page across tournaments, leagues, and competitions.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use InvalidArgumentException;

final readonly class Contact_Page_View_Model {

    /**
     * @param object|null       $tournament
     * @param object|null       $league
     * @param object|null       $competition
     * @param string            $object_name
     * @param int|string        $object_id
     * @param string            $season
     * @param string            $tab
     * @param string|null       $email_title
     * @param string|null       $email_intro
     * @param array<mixed>|null $email_body
     * @param string|null       $email_close
     * @param string            $email_message
     */
    public function __construct(
        public ?object $tournament = null,
        public ?object $league = null,
        public ?object $competition = null,
        public string $object_name = '',
        public int|string $object_id = 0,
        public string $season = '',
        public string $tab = 'compose',
        public ?string $email_title = null,
        public ?string $email_intro = null,
        public ?array $email_body = null,
        public ?string $email_close = null,
        public string $email_message = '',
    ) {
        $targets = ( null !== $this->tournament ? 1 : 0 )
            + ( null !== $this->league ? 1 : 0 )
            + ( null !== $this->competition ? 1 : 0 );

        if ( 1 !== $targets ) {
            throw new InvalidArgumentException( 'Contact_Page_View_Model requires exactly one of tournament, league, or competition.' );
        }
    }
}
