<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Ports;

/**
 * Port interface for club validation operations.
 */
interface Club_Validator_Interface {
    /**
     * Validate name.
     *
     * @param string|null $name Name.
     * @return self
     */
    public function name( ?string $name ): self;

    /**
     * Validate shortcode.
     *
     * @param string|null $shortcode Shortcode.
     * @return self
     */
    public function short_code( ?string $shortcode ): self;

    /**
     * Validate type.
     *
     * @param string|null $type Type.
     * @return self
     */
    public function type( ?string $type ): self;

    /**
     * Validate address.
     *
     * @param string|null $address Address.
     * @return self
     */
    public function address( ?string $address ): self;

    /**
     * Validate match secretary.
     *
     * @param int|null $match_secretary Match secretary player ID.
     * @param string   $error_field     Error field name.
     * @return self
     */
    public function match_secretary( ?int $match_secretary, string $error_field = 'match_secretary' ): self;

    /**
     * Validate telephone.
     *
     * @param string|null $telephone   Telephone number.
     * @param string      $error_field Error field name.
     * @return self
     */
    public function telephone( ?string $telephone, string $error_field = 'contactno' ): self;

    /**
     * Validate email.
     *
     * @param string|null $email               Email address.
     * @param int|null    $user_id             User ID.
     * @param bool        $email_required      Whether email is required.
     * @param string|null $field_ref           Field reference.
     * @param bool        $field_ref_override  Whether to override field reference.
     * @return self
     */
    public function email( ?string $email, ?int $user_id = null, bool $email_required = true, ?string $field_ref = null, bool $field_ref_override = false ): self;

    /**
     * Validate club role.
     *
     * @param int|null $club_role_id Club role ID.
     * @return self
     */
    public function club_role( ?int $club_role_id ): self;

    /**
     * Validate user.
     *
     * @param int|null $user_id     User ID.
     * @param string   $error_field Error field name.
     * @return self
     */
    public function user( ?int $user_id, string $error_field = 'userName' ): self;

    /**
     * Whether validation has errors.
     *
     * @return bool
     */
    public function has_errors(): bool;

    /**
     * Get error messages.
     *
     * @return array<int, string>
     */
    public function get_error_messages(): array;

    /**
     * Get first error message.
     *
     * @return string|null
     */
    public function get_first_error_message(): ?string;
}
