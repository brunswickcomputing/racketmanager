/**
 * @jest-environment jsdom
 */

import { initializePasswordStrength } from '../../../../src/js/features/forms/password-strength.js';

describe('features/forms/password-strength', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <form id="profile-form">
        <input type="text" id="user_login" value="testuser" />
        <input type="password" id="password" name="password" value="" />
        <input type="password" id="rePassword" name="rePassword" value="" />
        <div id="password-strength" style="display: none;"></div>
        <input type="submit" id="submit" value="Submit" />
      </form>
    `;
    window.jQuery = window.$ = require('jquery');
  });

  afterEach(() => {
    delete globalThis.wp;
    delete globalThis.pwsL10n;
    jest.restoreAllMocks();
  });

  test('shows empty message when password is empty', () => {
    initializePasswordStrength();
    const $pwd = jQuery('#password');
    $pwd.val('');
    $pwd.trigger('keyup');

    const $strength = jQuery('#password-strength');
    expect($strength.hasClass('empty')).toBe(true);
    expect($strength.text()).toBe('Empty');
    expect(jQuery('input[type=submit]').attr('disabled')).toBe('disabled');
  });

  test('falls back to score 0 and disables submit when wp.passwordStrength.meter throws an exception', () => {
    globalThis.wp = {
      passwordStrength: {
        userInputDisallowedList: jest.fn(() => ['disallowed']),
        meter: jest.fn(() => {
          throw new Error('Meter calculation error');
        })
      }
    };

    initializePasswordStrength();
    const $pwd = jQuery('#password');
    const $confirm = jQuery('#rePassword');
    $pwd.val('ValidPassword123!');
    $confirm.val('ValidPassword123!');
    $pwd.trigger('keyup');

    const $strength = jQuery('#password-strength');
    expect($strength.hasClass('bad')).toBe(true);
    expect($strength.text()).toBe('Bad');
    expect(jQuery('input[type=submit]').attr('disabled')).toBe('disabled');
  });

  test('catches and continues when userInputDisallowedList throws an exception', () => {
    const errorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    globalThis.wp = {
      passwordStrength: {
        userInputDisallowedList: jest.fn(() => {
          throw new Error('Disallowed list error');
        }),
        meter: jest.fn(() => 4)
      }
    };

    initializePasswordStrength();
    const $pwd = jQuery('#password');
    const $confirm = jQuery('#rePassword');
    $pwd.val('StrongPass123!');
    $confirm.val('StrongPass123!');
    $pwd.trigger('keyup');

    expect(errorSpy).toHaveBeenCalled();
    const $strength = jQuery('#password-strength');
    expect($strength.hasClass('strong')).toBe(true);
    expect(jQuery('input[type=submit]').attr('disabled')).toBeUndefined();
  });

  test('enables submit button when password score is 3 and confirm password is set', () => {
    globalThis.wp = {
      passwordStrength: {
        meter: jest.fn(() => 3)
      }
    };

    initializePasswordStrength();
    const $pwd = jQuery('#password');
    const $confirm = jQuery('#rePassword');
    $pwd.val('GoodPassword123');
    $confirm.val('GoodPassword123');
    $pwd.trigger('keyup');

    const $strength = jQuery('#password-strength');
    expect($strength.hasClass('good')).toBe(true);
    expect(jQuery('input[type=submit]').attr('disabled')).toBeUndefined();
  });

  test('handles mismatch score 5 by adding short class and disabling submit button', () => {
    globalThis.wp = {
      passwordStrength: {
        meter: jest.fn(() => 5)
      }
    };

    initializePasswordStrength();
    const $pwd = jQuery('#password');
    const $confirm = jQuery('#rePassword');
    $pwd.val('GoodPassword123');
    $confirm.val('Different123');
    $pwd.trigger('keyup');

    const $strength = jQuery('#password-strength');
    expect($strength.hasClass('short')).toBe(true);
    expect($strength.text()).toBe('Mismatch');
    expect(jQuery('input[type=submit]').attr('disabled')).toBe('disabled');
  });
});
