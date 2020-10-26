<?php
    if ( !is_user_logged_in() ) {
        return __( 'You must be signed in.', 'leaguemanager' );
    }

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && !empty( $_POST['action'] ) && $_POST['action'] == 'update-user' ) {
        if ( isset( $_POST['member_account_nonce_field'] ) && wp_verify_nonce( $_POST['member_account_nonce_field'], 'member_account_nonce' ) ) {
            $redirect_url = home_url( 'member-account' );
            $user = wp_get_current_user();
            $user_data = array(
                               'user_email' => sanitize_email( $_POST['username'] ),
                               'first_name' => sanitize_text_field( $_POST['firstname'] ),
                               'last_name' => sanitize_text_field( $_POST['lastname'] ),
                               'user_pass' => $_POST['pass1'],
                               'contactno' => sanitize_text_field( $_POST['contactno'] ),
                               'gender' => sanitize_text_field( $_POST['gender'] ),
                               'btm' => sanitize_text_field( $_POST['btm'] )
                               );
            $updates = false;
            $validationerrors = false;
            $validations = array();
            if ( empty($_POST['username']) ) {
                $validationerrors = true;
                $validations[] = 'email_field_empty';
            } elseif ( $user_data['user_email'] != $user->user_email ) {
                $updates = true;
            } else {
                unset($user_data['user_email']);
            }

            if ( empty($_POST['firstname']) ) {
                $validationerrors = true;
                $validations[] = 'firstname_field_empty';
            } elseif ( $_POST['firstname'] != get_user_meta($user->id,'first_name',true) ) {
                $updates = true;
            } else {
                unset($user_data['firstname']);
            }
            if ( empty($_POST['lastname']) ) {
                $validationerrors = true;
                $validations[] = 'lastname_field_empty';
            } elseif ( $_POST['lastname'] != get_user_meta($user->id,'last_name',true) ) {
                $updates = true;
            } else {
                unset($user_data['lastname']);
            }
            if ( empty($_POST['contactno']) ) {
                if ( empty(get_user_meta($user->id,'contactno',true)) ) {
                    unset($user_data['contactno']);
                } else {
                    $updates = true;
                }
            } elseif ( $_POST['contactno'] != get_user_meta($user->id,'contactno',true) ) {
                $updates = true;
            } else {
                unset($user_data['contactno']);
            }
            if ( empty($_POST['gender']) ) {
                $validationerrors = true;
                $validations[] = 'gender_field_empty';
            } elseif ( $_POST['gender'] != get_user_meta($user->id,'gender',true) ) {
                $updates = true;
            } else {
                unset($user_data['gender']);
            }
            if ( empty($_POST['btm']) ) {
                if ( empty(get_user_meta($user->id,'btm',true)) ) {
                    unset($user_data['btm']);
                } else {
                    $updates = true;
                }
            } elseif ( $_POST['btm'] != get_user_meta($user->id,'btm',true) ) {
                $updates = true;
            } else {
                unset($user_data['btm']);
            }
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
                $validationerrors = true;
                $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
                wp_redirect( $redirect_url );
                exit;
            } elseif ( !empty( $_POST['pass1'] ) ) {
                $updates = true;
            } else {
                unset($user_data['user_pass']);
            }
            if ( !$updates ) {
                if ( !$validationerrors ) {
                    $redirect_url = add_query_arg( 'error', 'no_updates', $redirect_url );
                }
            } else {
                if ( !$validationerrors ) {
                    foreach( $user_data as $key => $value ) {
                        // http://codex.wordpress.org/Function_Reference/wp_update_user
                        if( $key == 'contactno' ) {
                            $userid = update_user_meta( $user->ID, $key, $value );
                            unset( $user_data['contactno'] );
                        } elseif( $key == 'btm' ) {
                            $userid = update_user_meta( $user->ID, $key, $value );
                            unset( $user_data['btm'] );
                        } elseif( $key == 'gender' ) {
                            $userid = update_user_meta( $user->ID, $key, $value );
                            unset( $user_data['gender'] );
                        } elseif( $key == 'first_name' ) {
                            $userid = update_user_meta( $user->id, $key, $value );
                            $userid = wp_update_user( array( 'ID' => $user->ID, 'display_name' => $value.' '.sanitize_text_field( $_POST['lastname'] ) ) );
                            unset( $user_data['firstname'] );
                        } elseif( $key == 'last_name' ) {
                            $userid = update_user_meta( $user->id, $key, $value );
                            $userid = wp_update_user( array( 'ID' => $user->ID, 'display_name' => sanitize_text_field( $_POST['firstname'] ).' '.$value ) );
                            unset( $user_data['lastname'] );
                        } elseif ( $key == 'user_pass' ) {
                            $userid = wp_set_password( $user_data['user_pass'], $user->ID );
                            unset( $user_data['user_pass'] );
                            // Log-in again.
                            wp_set_auth_cookie($user->ID);
                            wp_set_current_user($user->ID);
                            do_action('wp_login', $user->user_login, $user);
                        } else {
                            $userid = wp_update_user( array( 'ID' => $user->ID, $key => $value ) );
                        }
                    }
                    $redirect_url = home_url( 'member-account' );
                    $redirect_url = add_query_arg( 'updated', 'true', $redirect_url );
                }
            }
            wp_redirect( $redirect_url );
            exit;
        }
    }
?>


