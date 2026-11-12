<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Application\Club\Ports\Club_Validator_Interface;

/**
 * Club validator adapter.
 */
final readonly class Club_Validator_Adapter implements Club_Validator_Interface {
    /**
     * Constructor.
     *
     * @param Validator_Club $validator Validator.
     */
    public function __construct(
        private Validator_Club $validator,
    ) {
    }

    /**
     * Set the name for validation.
     *
     * @param string|null $name The club name.
     *
     * @return $this
     */
    public function name( ?string $name ): self {
        $this->validator->name( $name );
        return $this;
    }

    /**
     * Set the short code for validation.
     *
     * @param string|null $shortcode The short code.
     *
     * @return $this
     */
    public function short_code( ?string $shortcode ): self {
        $this->validator->short_code( $shortcode );
        return $this;
    }

    /**
     * Set the club type for validation.
     *
     * @param string|null $type The club type.
     *
     * @return $this
     */
    public function type( ?string $type ): self {
        $this->validator->type( $type );
        return $this;
    }

    /**
     * Set the address for validation.
     *
     * @param string|null $address The address.
     *
     * @return $this
     */
    public function address( ?string $address ): self {
        $this->validator->address( $address );
        return $this;
    }

    /**
     * Set the match secretary for validation.
     *
     * @param int|null $match_secretary The match secretary ID.
     * @param string   $error_field The error field.
     *
     * @return $this
     */
    public function match_secretary( ?int $match_secretary, string $error_field = 'match_secretary' ): self {
        $this->validator->match_secretary( $match_secretary, $error_field );
        return $this;
    }

    /**
     * Set the telephone number for validation.
     *
     * @param string|null $telephone The telephone number.
     * @param string      $error_field The error field.
     *
     * @return $this
     */
    public function telephone( ?string $telephone, string $error_field = 'contactno' ): self {
        $this->validator->telephone( $telephone, $error_field );
        return $this;
    }

    /**
     * Set the email for validation.
     *
     * @param string|null $email The email address.
     * @param int|null    $user_id The user ID.
     * @param bool        $email_required Whether the email is required.
     * @param string|null $field_ref The field reference.
     * @param bool        $field_ref_override Whether to override the field reference.
     *
     * @return $this
     */
    public function email( ?string $email, ?int $user_id = null, bool $email_required = true, ?string $field_ref = null, bool $field_ref_override = false ): self {
        $this->validator->email( $email, $user_id, $email_required, $field_ref, $field_ref_override );
        return $this;
    }

    /**
     * Set the club role ID for validation.
     *
     * @param int|null $club_role_id The club role ID.
     *
     * @return $this
     */
    public function club_role( ?int $club_role_id ): self {
        $this->validator->club_role( $club_role_id );
        return $this;
    }

    /**
     * Set the user ID for validation.
     *
     * @param int|null $user_id The user ID.
     * @param string   $error_field The error field.
     *
     * @return $this
     */
    public function user( ?int $user_id, string $error_field = 'userName' ): self {
        $this->validator->user( $user_id, $error_field );
        return $this;
    }

    /**
     * Check if there are any validation errors.
     *
     * @return bool
     */
    public function has_errors(): bool {
        return ! empty( $this->validator->error );
    }

    /**
     * Get the error messages.
     *
     * @return array<int, string>
     */
    public function get_error_messages(): array {
        return $this->validator->err_msgs ?? [];
    }

    /**
     * Get the first error message.
     *
     * @return string|null
     */
    public function get_first_error_message(): ?string {
        return $this->validator->err_msgs[0] ?? null;
    }
}
