<?php
declare( strict_types=1 );
/**
 * Message API: message class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Message
 */

namespace Racketmanager\Domain\Communication;

use Racketmanager\Domain\DTO\Communication\Message_Hydration_DTO;

/**
 * Class to implement the message object
 */
class Message {
    /**
     * ID
     *
     * @var int
     */
    public private(set) int $id;
    /**
     * Subject
     *
     * @var string
     */
    public private(set) string $subject;
    /**
     * User ID
     *
     * @var int
     */
    public private(set) int $userid;
    /**
     * Status
     *
     * @var string
     */
    public private(set) string $status;
    /**
     * Date
     *
     * @var string
     */
    public private(set) string $date;
    /**
     * Sender
     *
     * @var string
     */
    public private(set) string $sender;
    /**
     * From name
     *
     * @var string
     */
    public private(set) string $from_name = '';
    /**
     * From email
     *
     * @var string
     */
    public private(set) string $from_email = '';
    /**
     * Message detail stored in database
     *
     * @var string
     */
    public private(set) string $message_object;

    /**
     * Construct class instance
     *
     * @param Message_Hydration_DTO|null $dto hydration DTO.
     */
    public function __construct( ?Message_Hydration_DTO $dto = null ) {
        if ( null === $dto ) {
            return;
        }

        if ( null !== $dto->id ) {
            $this->id = $dto->id;
        }
        if ( null !== $dto->subject ) {
            $this->subject = $dto->subject;
        }
        if ( null !== $dto->userid ) {
            $this->userid = $dto->userid;
        }
        if ( null !== $dto->status ) {
            $this->status = $dto->status;
        }
        if ( null !== $dto->date ) {
            $this->date = $dto->date;
        }
        if ( null !== $dto->sender ) {
            $this->sender = $dto->sender;
            $this->parse_sender( $dto->sender );
        }
        if ( null !== $dto->message_object ) {
            $this->message_object = $dto->message_object;
        }
    }

    /**
     * Parse sender string into name and email.
     *
     * @param string $sender Sender string.
     */
    private function parse_sender( string $sender ): void {
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

    /**
     * Create from a generic object (e.g., database row) via Message_Factory.
     *
     * @param object $row message row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Message_Factory::from_object( $row );
    }

    /**
     * Create from an array via Message_Factory.
     *
     * @param array $data message data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Message_Factory::from_array( $data );
    }

    /**
     * Get message ID.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id ?? 0;
    }

    /**
     * Set message ID.
     *
     * @param int $id Message ID.
     */
    public function set_id( int $id ): void {
        $this->id = $id;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function get_subject(): string {
        return $this->subject ?? '';
    }

    /**
     * Set subject.
     *
     * @param string $subject Subject.
     */
    public function set_subject( string $subject ): void {
        $this->subject = $subject;
    }

    /**
     * Get user ID.
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->userid ?? 0;
    }

    /**
     * Set user ID.
     *
     * @param int $user_id User ID.
     */
    public function set_user_id( int $user_id ): void {
        $this->userid = $user_id;
    }

    /**
     * Get user ID (alias).
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid ?? 0;
    }

    /**
     * Set user ID (alias).
     *
     * @param int $userid User ID.
     */
    public function set_userid( int $userid ): void {
        $this->userid = $userid;
    }

    /**
     * Get message status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status ?? '';
    }

    /**
     * Set message status.
     *
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        $this->status = $status;
    }

    /**
     * Get date.
     *
     * @return string
     */
    public function get_date(): string {
        return $this->date ?? '';
    }

    /**
     * Set date.
     *
     * @param string $date Date string.
     */
    public function set_date( string $date ): void {
        $this->date = $date;
    }

    /**
     * Get sender.
     *
     * @return string
     */
    public function get_sender(): string {
        return $this->sender ?? '';
    }

    /**
     * Set sender and reparse from_name and from_email.
     *
     * @param string $sender Sender string.
     */
    public function set_sender( string $sender ): void {
        $this->sender = $sender;
        $this->parse_sender( $sender );
    }

    /**
     * Get from name.
     *
     * @return string
     */
    public function get_from_name(): string {
        return $this->from_name;
    }

    /**
     * Set from name.
     *
     * @param string $from_name From name.
     */
    public function set_from_name( string $from_name ): void {
        $this->from_name = $from_name;
    }

    /**
     * Get from email.
     *
     * @return string
     */
    public function get_from_email(): string {
        return $this->from_email;
    }

    /**
     * Set from email.
     *
     * @param string $from_email From email.
     */
    public function set_from_email( string $from_email ): void {
        $this->from_email = $from_email;
    }

    /**
     * Get message object/detail.
     *
     * @return string
     */
    public function get_message_object(): string {
        return $this->message_object ?? '';
    }

    /**
     * Set message object/detail.
     *
     * @param string $message_object Message object detail.
     */
    public function set_message_object( string $message_object ): void {
        $this->message_object = $message_object;
    }

    /**
     * Check if message is read.
     *
     * @return bool
     */
    public function is_read(): bool {
        return '0' === ( $this->status ?? '' );
    }

    /**
     * Check if message is unread.
     *
     * @return bool
     */
    public function is_unread(): bool {
        return '1' === ( $this->status ?? '' );
    }

    /**
     * Mark message as read.
     */
    public function mark_as_read(): void {
        $this->status = '0';
    }

    /**
     * Mark message as unread.
     */
    public function mark_as_unread(): void {
        $this->status = '1';
    }

    /**
     * Check if message is owned by a specific user.
     *
     * @param int $user_id User ID.
     *
     * @return bool
     */
    public function is_owned_by( int $user_id ): bool {
        return isset( $this->userid ) && $this->userid === $user_id;
    }
}
