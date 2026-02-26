<?php
/**
 * Notify_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\RacketManager;

class Notify_Service {

    private RacketManager $racketmanager;

    public function __construct( RacketManager $plugin_instance ) {
        $this->racketmanager = $plugin_instance;
    }

    /**
     * Email entry form
     *
     * @param string $template email template to use.
     * @param array $template_args template arguments.
     * @param string $email_to email address to send.
     * @param string $email_subject email subject.
     * @param array $headers email headers.
     */
    public function email_entry_form( string $template, array $template_args, string $email_to, string $email_subject, array $headers ): void {
        $email_message = $this->racketmanager->shortcodes->load_template(
            $template,
            $template_args,
            'email'
        );
        wp_mail( $email_to, $email_subject, $email_message, $headers );
    }

}
