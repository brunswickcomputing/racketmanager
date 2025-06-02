jQuery( document ).ready( function( $ ) {
  // trigger the wdmChkPwdStrength
  $( 'body' ).on( 'keyup', 'input[name=password], input[name=rePassword]', function() {
    wdmChkPwdStrength(
      // password field
      $('#password'),
      // confirm password field
      $('#rePassword'),
      // strength status
      $('#password-strength'),
      // Submit button
      $('input[type=submit]'),
      // blacklisted words which should not be a part of the password
      ['admin', 'happy', 'hello', '1234', $('#user_login').val()]
    );
  });
});
function wdmChkPwdStrength( $pwd,  $confirmPwd, $strengthStatus, $submitBtn, blacklistedWords ) {
  const pwd = $pwd.val();
  const confirmPwd = $confirmPwd.val();

  // extend the blacklisted words array with those from the site data
  blacklistedWords = blacklistedWords.concat( wp.passwordStrength.userInputDisallowedList() )

  // every time a letter is typed, reset the submit button and the strength meter status
  // disable the submit button
  $submitBtn.attr( 'disabled', 'disabled' );
  $strengthStatus.removeClass( 'short bad good strong' );
  jQuery('#password-strength').show();

  if ( ! pwd ) {
  			$strengthStatus.addClass( 'empty' ).html( pwsL10n.empty );
        return 0;
  		}

  // calculate the password strength
  const pwdStrength = wp.passwordStrength.meter(pwd, blacklistedWords, confirmPwd);

  // check the password strength
  switch ( pwdStrength ) {

    case 2:
    $strengthStatus.addClass( 'bad' ).html( pwsL10n.bad );
    break;

    case 3:
    $strengthStatus.addClass( 'good' ).html( pwsL10n.good );
    break;

    case 4:
    $strengthStatus.addClass( 'strong' ).html( pwsL10n.strong );
    break;

    case 5:
    $strengthStatus.addClass( 'short' ).html( pwsL10n.mismatch );
    break;

    default:
    $strengthStatus.addClass( 'bad' ).html( pwsL10n.bad );
  }
  // set the status of the submit button
  if ( (4 === pwdStrength || 3 === pwdStrength) && '' !== confirmPwd.trim() ) {
    $submitBtn.removeAttr( 'disabled' );
  }

  return pwdStrength;
}
