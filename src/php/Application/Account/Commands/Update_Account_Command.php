<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Account\Commands;

/**
 * Command for updating user account details via AJAX.
 */
class Update_Account_Command {
    public function __construct(
        public readonly ?string $username,
        public readonly ?string $firstname,
        public readonly ?string $lastname,
        public readonly ?string $contactno,
        public readonly ?string $gender,
        public readonly ?int $btm,
        public readonly ?int $year_of_birth,
        public readonly ?string $password,
        public readonly ?string $re_password,
        public readonly array $opt_ins = array()
    ) {
    }

    public static function from_post( array $post ): self {
        return new self(
            username: isset( $post['username'] ) ? sanitize_email( wp_unslash( $post['username'] ) ) : null,
            firstname: isset( $post['firstname'] ) ? sanitize_text_field( wp_unslash( $post['firstname'] ) ) : null,
            lastname: isset( $post['lastname'] ) ? sanitize_text_field( wp_unslash( $post['lastname'] ) ) : null,
            contactno: isset( $post['contactno'] ) ? sanitize_text_field( wp_unslash( $post['contactno'] ) ) : null,
            gender: isset( $post['gender'] ) ? sanitize_text_field( wp_unslash( $post['gender'] ) ) : null,
            btm: empty( $post['btm'] ) ? null : (int) $post['btm'],
            year_of_birth: empty( $post['year_of_birth'] ) ? null : (int) $post['year_of_birth'],
            password: isset( $post['password'] ) ? sanitize_text_field( wp_unslash( $post['password'] ) ) : null,
            re_password: isset( $post['rePassword'] ) ? sanitize_text_field( wp_unslash( $post['rePassword'] ) ) : null,
            opt_ins: isset( $post['opt_in'] ) && is_array( $post['opt_in'] ) ? wp_unslash( $post['opt_in'] ) : array()
        );
    }
}
