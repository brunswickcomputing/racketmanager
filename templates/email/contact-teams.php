<?php
/**
 * Contact league teams email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $intro */
/** @var array  $body */
/** @var string $closing */
/** @var string $closing_text */
/** @var string $object_type */
/** @var string $title */
/** @var string $paragraph */
/** @var array $vars */
/** @var object|null $vm */
$intro         = $vars['intro'] ?? $vm->intro ?? $intro ?? '';
$body          = $vars['body'] ?? $vm->body ?? $body ?? [];
$closing_text  = $vars['closing_text'] ?? $vm->closing_text ?? $closing_text ?? '';
$object_type   = $vars['object_type'] ?? $vm->object_type ?? $object_type ?? '';
$title_text    = $vars['title_text'] ?? $vm->title_text ?? $title_text ?? '';
$tournament    = $vars['tournament'] ?? $vm->tournament ?? $tournament ?? null;
$email_subject = $vars['email_subject'] ?? $vm->email_subject ?? $email_subject ?? ( ! empty( $title_text ) ? $title_text : __( 'Team Contact', 'racketmanager' ) );
require 'email-header.php';
$title_align = 'center';
require $title;
if ( isset( $tournament ) ) {
    $paragraph_text = __( 'Dear player', 'racketmanager' );
} else {
    $paragraph_text = __( 'Dear captain', 'racketmanager' );
}
require $paragraph;
if ( $intro ) {
    $paragraph_text = $intro;
    require $paragraph;
}
foreach ( $body as $i => $body_entry ) {
    if ( $body_entry ) {
        $paragraph_imbed = true;
        $paragraph_text = $body_entry;
        require $paragraph;
    }
}
if ( $closing_text ) {
    $paragraph_text = $closing_text;
    require $paragraph;
}
require $closing;
require 'email-footer.php';
