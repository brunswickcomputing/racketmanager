<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Util\Util;
use function __;

class Get_Member_Account_Form_Handler {
    public function __construct(
        private readonly User_Repository_Interface $user_repository
    ) {}

    public function handle( Get_Member_Account_Form_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Shortcode_View_Model::for_html(
                __( 'You must be signed in to access this page', 'racketmanager' )
            );
        }

        $user = $this->user_repository->find( $query->user_id );

        $opt_in_choices = Util::get_email_opt_ins();

        return Shortcode_View_Model::for_template( 'form-member-account', [
            'user'           => $user,
            'opt_in_choices' => $opt_in_choices,
        ], 'form' );
    }
}
