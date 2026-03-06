<?php
/**
 * Tournament contact request DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Tournament
 */

namespace Racketmanager\Domain\DTO\Tournament;

final readonly class Tournament_Contact_Request_DTO {

    public ?string $season;
    public string $email_title;
    public string $email_intro;
    public array $email_body;
    public string $email_close;

    public function __construct( array $source ) {
        $this->season = isset( $source['season'] )
            ? sanitize_text_field( wp_unslash( strval( $source['season'] ) ) )
            : null;

        $this->email_title = isset( $source['contactTitle'] )
            ? sanitize_text_field( wp_unslash( strval( $source['contactTitle'] ) ) )
            : '';

        $this->email_intro = isset( $source['contactIntro'] )
            ? sanitize_textarea_field( wp_unslash( strval( $source['contactIntro'] ) ) )
            : '';

        $email_body = array();
        if ( isset( $source['contactBody'] ) && is_array( $source['contactBody'] ) ) {
            foreach ( $source['contactBody'] as $key => $paragraph ) {
                $email_body[ $key ] = is_scalar( $paragraph ) ? wp_unslash( strval( $paragraph ) ) : '';
            }
        }
        $this->email_body = $email_body;

        $this->email_close = isset( $source['contactClose'] )
            ? sanitize_textarea_field( wp_unslash( strval( $source['contactClose'] ) ) )
            : '';
    }
}
