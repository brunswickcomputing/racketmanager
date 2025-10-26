// password-strength.js
// Move of legacy password-strength-meter-mediator.js into main module bundle
// Requires WordPress core script 'password-strength-meter' to be enqueued and pwsL10n localized

function meterPasswordStrength($pwd, $confirmPwd, $strengthStatus, $submitBtn, blacklistedWords) {
  const pwd = $pwd.val();
  const confirmPwd = $confirmPwd.val();

  // extend the blacklisted words array with those from the site data
  try {
    if (globalThis.wp && wp.passwordStrength && typeof wp.passwordStrength.userInputDisallowedList === 'function') {
      blacklistedWords = blacklistedWords.concat(wp.passwordStrength.userInputDisallowedList());
    }
  } catch (_) { /* no-op */ }

  // every time a letter is typed, reset the submit button and the strength meter status
  $submitBtn.attr('disabled', 'disabled');
  $strengthStatus.removeClass('empty short bad good strong');
  jQuery('#password-strength').show();

  const t = (globalThis.pwsL10n || {
    empty: "Empty",
    short: "Too short",
    bad: "Bad",
    good: "Good",
    strong: "Strong",
    mismatch: "Mismatch"
  });

  if (!pwd) {
    $strengthStatus.addClass('empty').html(t.empty);
    return 0;
  }

  // calculate the password strength
  let pwdStrength = 0;
  try {
    if (globalThis.wp && wp.passwordStrength && typeof wp.passwordStrength.meter === 'function') {
      pwdStrength = wp.passwordStrength.meter(pwd, blacklistedWords, confirmPwd);
    }
  } catch (_) { /* default 0 */ }

  // check the password strength
  switch (pwdStrength) {
    case 2:
      $strengthStatus.addClass('bad').html(t.bad);
      break;
    case 3:
      $strengthStatus.addClass('good').html(t.good);
      break;
    case 4:
      $strengthStatus.addClass('strong').html(t.strong);
      break;
    case 5:
      $strengthStatus.addClass('short').html(t.mismatch);
      break;
    default:
      $strengthStatus.addClass('bad').html(t.bad);
  }
  // set the status of the submit button
  if ((4 === pwdStrength || 3 === pwdStrength) && '' !== (confirmPwd || '').trim()) {
    $submitBtn.removeAttr('disabled');
  }

  return pwdStrength;
}

export function initializePasswordStrength() {
  // If core meter is not present, bail gracefully (handler would do nothing useful)
  if (!globalThis.wp || !wp.passwordStrength) {
    // Still attach handler so once script loads dynamically it works; but usually WP enqueues it early.
  }

  const selector = 'input[name=password], input[name=rePassword]';

  // Delegate on body; remove previous namespaced handler to avoid duplicates
  jQuery(document)
    .off('keyup.racketmanager.pwdStrength', selector)
    .on('keyup.racketmanager.pwdStrength', selector, function () {
      meterPasswordStrength(
        // password field
        jQuery('#password'),
        // confirm password field
        jQuery('#rePassword'),
        // strength status
        jQuery('#password-strength'),
        // Submit button
        jQuery('input[type=submit]'),
        // blacklisted words which should not be a part of the password
        ['admin', 'happy', 'hello', '1234', jQuery('#user_login').val()]
      );
    });
}
