<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Commands;

/**
 * Command DTO for updating player profile details.
 */
readonly class Update_Player_Command {
    /**
     * @param int|null $player_id
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string|null $gender
     * @param string|null $email
     * @param string|null $telephone
     * @param string|null $btm
     * @param int|null $year_of_birth
     * @param bool|null $locked
     * @param int|null $club_id
     * @param string|null $page_referrer
     */
    public function __construct(
        public ?int $player_id,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $gender = null,
        public ?string $email = null,
        public ?string $telephone = null,
        public ?string $btm = null,
        public ?int $year_of_birth = null,
        public ?bool $locked = null,
        public ?int $club_id = null,
        public ?string $page_referrer = null
    ) {
    }

    /**
     * Build command from POST / request array.
     *
     * @param array<string, mixed> $post POST request array.
     * @return self
     */
    public static function from_post( array $post ): self {
        return new self(
            player_id: self::parse_positive_int( $post, 'playerId', 'id' ),
            first_name: self::parse_string( $post, 'firstname', 'first_name' ),
            last_name: self::parse_string( $post, 'surname', 'last_name' ),
            gender: self::parse_string( $post, 'gender' ),
            email: self::parse_string( $post, 'email' ),
            telephone: self::parse_string( $post, 'contactno', 'telephone' ),
            btm: self::parse_string( $post, 'btm' ),
            year_of_birth: self::parse_positive_int( $post, 'year_of_birth', 'dob' ),
            locked: self::parse_bool( $post, 'locked' ),
            club_id: self::parse_positive_int( $post, 'club_Id', 'club_id', 'club' ),
            page_referrer: self::parse_string( $post, 'page_referrer' )
        );
    }

    /**
     * Extract, sanitize, and trim the first non-empty string value for the given keys.
     *
     * @param array<string, mixed> $post POST request array.
     * @param string ...$keys Candidate keys in fallback order.
     * @return string|null Sanitized non-empty string, or null if none match.
     */
    private static function parse_string( array $post, string ...$keys ): ?string {
        foreach ( $keys as $key ) {
            if ( isset( $post[ $key ] ) && '' !== strval( $post[ $key ] ) ) {
                $value = trim( sanitize_text_field( wp_unslash( (string) $post[ $key ] ) ) );
                if ( '' !== $value ) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Extract the first positive integer value (> 0) for the given keys.
     *
     * @param array<string, mixed> $post POST request array.
     * @param string ...$keys Candidate keys in fallback order.
     * @return int|null Positive integer, or null if missing or not positive.
     */
    private static function parse_positive_int( array $post, string ...$keys ): ?int {
        foreach ( $keys as $key ) {
            if ( isset( $post[ $key ] ) && '' !== strval( $post[ $key ] ) ) {
                $value = intval( $post[ $key ] );
                if ( $value > 0 ) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Extract a boolean value indicating whether the key is present and not empty.
     *
     * @param array<string, mixed> $post POST request array.
     * @param string $key Request key to evaluate.
     * @return bool|null True if non-empty, false if empty, null if not present.
     */
    private static function parse_bool( array $post, string $key ): ?bool {
        return isset( $post[ $key ] ) ? ! empty( $post[ $key ] ) : null;
    }

    /**
     * Convert command back to associative array for legacy service integration.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        $data = [
            'id'            => $this->player_id,
            'playerId'      => $this->player_id,
            'firstname'     => $this->first_name ?? '',
            'surname'       => $this->last_name ?? '',
            'gender'        => $this->gender ?? '',
            'email'         => $this->email ?? '',
            'contactno'     => $this->telephone ?? '',
            'btm'           => $this->btm ?? '',
            'year_of_birth' => $this->year_of_birth,
            'club_Id'       => $this->club_id,
            'page_referrer' => $this->page_referrer,
        ];

        if ( null !== $this->locked ) {
            $data['locked'] = $this->locked ? 'Locked' : null;
        }

        return array_filter( $data, fn( $v ) => null !== $v );
    }
}
