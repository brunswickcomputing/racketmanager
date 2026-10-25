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
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $player_id = isset( $post['playerId'] ) && '' !== strval( $post['playerId'] )
            ? intval( $post['playerId'] )
            : ( isset( $post['id'] ) && '' !== strval( $post['id'] ) ? intval( $post['id'] ) : null );

        $first_name = isset( $post['firstname'] )
            ? trim( sanitize_text_field( wp_unslash( (string) $post['firstname'] ) ) )
            : ( isset( $post['first_name'] ) ? trim( sanitize_text_field( wp_unslash( (string) $post['first_name'] ) ) ) : null );

        $last_name = isset( $post['surname'] )
            ? trim( sanitize_text_field( wp_unslash( (string) $post['surname'] ) ) )
            : ( isset( $post['last_name'] ) ? trim( sanitize_text_field( wp_unslash( (string) $post['last_name'] ) ) ) : null );

        $gender = isset( $post['gender'] ) && '' !== strval( $post['gender'] )
            ? sanitize_text_field( wp_unslash( (string) $post['gender'] ) )
            : null;

        $email = isset( $post['email'] ) && '' !== strval( $post['email'] )
            ? sanitize_text_field( wp_unslash( (string) $post['email'] ) )
            : null;

        $telephone = isset( $post['contactno'] ) && '' !== strval( $post['contactno'] )
            ? sanitize_text_field( wp_unslash( (string) $post['contactno'] ) )
            : ( isset( $post['telephone'] ) && '' !== strval( $post['telephone'] ) ? sanitize_text_field( wp_unslash( (string) $post['telephone'] ) ) : null );

        $btm = isset( $post['btm'] ) && '' !== strval( $post['btm'] )
            ? sanitize_text_field( wp_unslash( (string) $post['btm'] ) )
            : null;

        $year_of_birth = isset( $post['year_of_birth'] ) && '' !== strval( $post['year_of_birth'] )
            ? intval( $post['year_of_birth'] )
            : ( isset( $post['dob'] ) && '' !== strval( $post['dob'] ) ? intval( $post['dob'] ) : null );

        $locked = isset( $post['locked'] ) ? ! empty( $post['locked'] ) : null;

        $club_id = isset( $post['club_Id'] ) && '' !== strval( $post['club_Id'] )
            ? intval( $post['club_Id'] )
            : ( isset( $post['club_id'] ) && '' !== strval( $post['club_id'] ) ? intval( $post['club_id'] ) : ( isset( $post['club'] ) && '' !== strval( $post['club'] ) ? intval( $post['club'] ) : null ) );

        $page_referrer = isset( $post['page_referrer'] ) && '' !== strval( $post['page_referrer'] )
            ? sanitize_text_field( wp_unslash( (string) $post['page_referrer'] ) )
            : null;

        return new self(
            player_id: $player_id > 0 ? $player_id : null,
            first_name: '' !== $first_name ? $first_name : null,
            last_name: '' !== $last_name ? $last_name : null,
            gender: $gender,
            email: '' !== $email ? $email : null,
            telephone: '' !== $telephone ? $telephone : null,
            btm: '' !== $btm ? $btm : null,
            year_of_birth: $year_of_birth > 0 ? $year_of_birth : null,
            locked: $locked,
            club_id: $club_id > 0 ? $club_id : null,
            page_referrer: $page_referrer
        );
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
