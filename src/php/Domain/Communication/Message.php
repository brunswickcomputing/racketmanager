<?php
/**
 * Message API: message class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Message
 */

namespace Racketmanager\Domain\Communication;

/**
 * Class to implement the message object
 */
class Message {
    /**
     * Id
     *
     * @var int
     */
    public int $id;
    /**
     * Subject
     *
     * @var string
     */
    public string $subject;
    /**
     * Userid
     *
     * @var int
     */
    public int $userid;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /**
     * Date
     *
     * @var string
     */
    public string $date;
    /**
     * Sender
     *
     * @var string
     */
    public string $sender;
    /**
     * From name
     *
     * @var string
     */
    public string $from_name = '';
    /**
     * From email
     *
     * @var string
     */
    public string $from_email = '';
    /**
     * Message detail stored in database
     *
     * @var string
     */
    public string $message_object;

    /**
     * Construct class instance
     *
     * @param object|null $message message object.
     */
    public function __construct( ?object $message = null ) {
        if ( ! is_null( $message ) ) {
            foreach ( get_object_vars( $message ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( isset( $this->sender ) ) {
                $sender           = $this->sender;
                $bracket_pos      = strpos( $sender, '<' );
                $this->from_name  = '';
                $this->from_email = '';
                if ( false !== $bracket_pos ) {
                    // Text before the bracketed email is the "From" name.
                    if ( $bracket_pos > 0 ) {
                        $from_name       = substr( $sender, 0, $bracket_pos );
                        $from_name       = str_replace( '"', '', $from_name );
                        $from_name       = trim( $from_name );
                        $this->from_name = $from_name;
                    }
                    $from_email       = substr( $sender, $bracket_pos + 1 );
                    $from_email       = str_replace( '>', '', $from_email );
                    $from_email       = trim( $from_email );
                    $this->from_email = $from_email;
                }
            }
        }
    }

    /**
     * Set message status
     *
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        $this->status = $status;
    }
}
