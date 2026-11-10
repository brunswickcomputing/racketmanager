<?php
/**
 * RacketManager-Admin API: Admin_Match_Report class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use function Racketmanager\match_dropdown;
use function Racketmanager\season_dropdown;

/**
 * Handles match report meta-box rendering and post synchronisation in WordPress admin.
 *
 * @package RacketManager
 * @subpackage Admin
 */
class Admin_Match_Report {

    /**
     * League repository instance.
     *
     * @var League_Repository_Interface|null
     */
    private ?League_Repository_Interface $league_repository;

    /**
     * Fixture repository instance.
     *
     * @var Fixture_Repository_Interface|null
     */
    private ?Fixture_Repository_Interface $fixture_repository;

    /**
     * Constructor.
     *
     * @param League_Repository_Interface|null  $league_repository  League repository instance.
     * @param Fixture_Repository_Interface|null $fixture_repository Fixture repository instance.
     */
    public function __construct(
        ?League_Repository_Interface $league_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null
    ) {
        $this->league_repository  = $league_repository;
        $this->fixture_repository = $fixture_repository;
    }

    /**
     * Add meta box to the post screen
     *
     * @param object $post Post details.
     *
     * @return void
     */
    public function add_meta_box( object $post ): void {
        $leagues = $this->league_repository?->find_names() ?? [];
        if ( empty( $leagues ) ) {
            return;
        }

        $context     = $this->get_meta_box_match_context( $post );
        $match_id    = $context['match_id'];
        $league_id   = $context['league_id'];
        $curr_league = $context['curr_league'];
        $match       = $context['match'];

        wp_nonce_field( 'racketmanager_edit_match_report_' . $post->ID, 'racketmanager_match_report_nonce', false );
        ?>
        <input type='hidden' name='curr_match_id' value="<?php echo esc_attr( (string) $match_id ); ?>" />
        <div class="container">
            <div class="row mb-3">
                <div class="col-auto">
                    <div class="form-floating">
                        <select name='league_id' class='form-select' id='league_id' data-action="get-season-dropdown">
                            <option value="0"><?php esc_html_e( 'Choose League', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $leagues as $league ) {
                                ?>
                                <option value="<?php echo esc_attr( (string) $league->id ); ?>" <?php selected( $league_id, $league->id ); ?>><?php echo esc_html( $league->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-auto">
                    <div class="form-floating" id="seasons">
                        <?php
                        if ( $match && $curr_league ) {
                            echo season_dropdown( $curr_league->id, array( 'season' => $curr_league->get_season() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-auto">
                    <div class="form-floating" id="matches">
                        <?php
                        if ( $match && $curr_league ) {
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- match_dropdown generates safe HTML markup.
                            echo match_dropdown(
                                $curr_league->id,
                                array(
                                    'season'   => $curr_league->get_season(),
                                    'match_id' => $match->id,
                                )
                            );
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <br style="clear: both;" />
        <?php
    }

    /**
     * Update post-id for a match report
     *
     * @param int $post_id Post ID being edited or published.
     *
     * @return void
     */
    public function edit_match_report( int $post_id = 0 ): void {
        $authorized_post_id = $this->resolve_authorized_match_report_post_id( $post_id );
        if ( 0 === $authorized_post_id ) {
            return;
        }

        if ( ! $this->verify_match_report_nonce( $authorized_post_id ) ) {
            return;
        }

        $this->sync_fixture_match_report( $authorized_post_id );
    }

    /**
     * Resolve match and league context for post meta box.
     *
     * @param object $post Post details.
     *
     * @return array<string, mixed>
     */
    private function get_meta_box_match_context( object $post ): array {
        $context = [
            'match_id'    => 0,
            'league_id'   => 0,
            'curr_league' => null,
            'match'       => null,
        ];

        $post_id = (int) ( $post->ID ?? 0 );
        if ( 0 === $post_id ) {
            return $context;
        }

        $match = $this->fixture_repository?->find_by_post( $post_id );
        if ( ! $match ) {
            return $context;
        }

        $league_id            = $match->league_id ?? 0;
        $context['match_id']  = $match->id ?? 0;
        $context['league_id'] = $league_id;
        $context['match']     = $match;

        if ( $league_id > 0 && null !== $this->league_repository ) {
            $context['curr_league'] = $this->league_repository->find_by_id( $league_id );
        }

        return $context;
    }

    /**
     * Resolve and authorise the target post ID for a match report edit.
     *
     * @param int $post_id Post ID being edited or published.
     *
     * @return int Resolved post ID on success, or 0 on failure.
     */
    private function resolve_authorized_match_report_post_id( int $post_id ): int {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return 0;
        }

        $post_id_from_post = $this->parse_post_digit_id( 'post_ID' );
        $target_post_id    = 0 === $post_id ? ( $post_id_from_post ?? 0 ) : $post_id;

        $has_mismatch = $post_id > 0 && $post_id_from_post > 0 && $post_id !== $post_id_from_post;
        if ( null === $post_id_from_post || $has_mismatch || $target_post_id <= 0 || ! current_user_can( 'edit_post', $target_post_id ) ) {
            return 0;
        }

        return $target_post_id;
    }

    /**
     * Verify the nonce for match report editing.
     *
     * @param int $post_id Post ID being edited or published.
     *
     * @return bool True if the nonce is valid, false otherwise.
     */
    private function verify_match_report_nonce( int $post_id ): bool {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in this method.
        if ( ! isset( $_POST['racketmanager_match_report_nonce'] ) || ! is_string( $_POST['racketmanager_match_report_nonce'] ) ) {
            return false;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['racketmanager_match_report_nonce'] ) );
        return false !== wp_verify_nonce( $nonce, 'racketmanager_edit_match_report_' . $post_id );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Synchronise fixture associations for a match report.
     *
     * @param int $post_id Post ID being edited or published.
     *
     * @return void
     */
    private function sync_fixture_match_report( int $post_id ): void {
        $match_id      = $this->parse_post_digit_id( 'match_id' );
        $curr_match_id = $this->parse_post_digit_id( 'curr_match_id' );

        if ( null === $match_id || null === $curr_match_id ) {
            return;
        }

        $existing_match    = $this->fixture_repository?->find_by_post( $post_id );
        $existing_match_id = $existing_match?->id ?? 0;

        if ( $curr_match_id !== $existing_match_id ) {
            return;
        }

        if ( $match_id <= 0 || $curr_match_id === $match_id || ! $this->is_target_fixture_claimable( $match_id, $post_id ) ) {
            return;
        }

        $this->transfer_fixture_to_post( $match_id, $curr_match_id, $post_id );
    }

    /**
     * Determine whether the target fixture can be claimed by the post.
     *
     * @param int $match_id Target match/fixture ID.
     * @param int $post_id  Post ID claiming the fixture.
     *
     * @return bool True if claimable (unassigned or already assigned to post), false if claimed by another post.
     */
    private function is_target_fixture_claimable( int $match_id, int $post_id ): bool {
        $target_match   = $this->fixture_repository?->find_by_id( $match_id );
        $target_post_id = $target_match?->post_id ?? 0;

        return 0 === $target_post_id || $target_post_id === $post_id;
    }

    /**
     * Transfer fixture association to the target post and detach any previous fixture.
     *
     * @param int $match_id      Target match/fixture ID.
     * @param int $curr_match_id Current match/fixture ID previously linked to the post.
     * @param int $post_id       Post ID receiving the fixture link.
     *
     * @return void
     */
    private function transfer_fixture_to_post( int $match_id, int $curr_match_id, int $post_id ): void {
        $this->fixture_repository?->update_post_id( $match_id, $post_id );
        if ( $curr_match_id > 0 ) {
            $this->fixture_repository?->update_post_id( $curr_match_id, 0 );
        }
    }

    /**
     * Parse a non-negative digit ID from POST data.
     *
     * Array values are deliberately rejected as a security validation measure against malformed payloads.
     *
     * @param string $key POST key to parse.
     *
     * @return int|null Parsed integer ID, 0 if absent, or null if invalid or array.
     */
    private function parse_post_digit_id( string $key ): ?int {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in caller or validated as part of post authorisation.
        if ( ! isset( $_POST[ $key ] ) ) {
            return 0;
        }

        if ( is_array( $_POST[ $key ] ) ) {
            return null;
        }

        $raw_value = sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
        return ctype_digit( $raw_value ) ? (int) $raw_value : null;
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }
}
