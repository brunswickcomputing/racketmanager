/**
 * @jest-environment jsdom
 */

import { showMessage, setLoading } from '../../../../src/js/features/payments/stripe-helpers.js';

describe('features/payments/stripe-helpers', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="payment-block"></div>
      <div id="payment-message" class="hidden"></div>
    `;
    window.jQuery = window.$ = require('jquery');
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
  });

  describe('showMessage', () => {
    test('displays message and auto-hides after 4000ms when container exists', () => {
      const container = document.getElementById('payment-message');
      showMessage('Payment failed, please try again.');

      expect(container.classList.contains('hidden')).toBe(false);
      expect(container.textContent).toBe('Payment failed, please try again.');

      jest.advanceTimersByTime(4000);

      expect(container.classList.contains('hidden')).toBe(true);
      expect(container.textContent).toBe('');
    });

    test('logs warning when payment-message container is missing', () => {
      document.getElementById('payment-message').remove();
      const warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});

      showMessage('Some message');

      expect(warnSpy).toHaveBeenCalledWith('payment-message container not found');
    });

    test('suppresses error when payment-message container is missing and console.warn throws', () => {
      document.getElementById('payment-message').remove();
      const warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {
        throw new Error('Blocked console access');
      });

      expect(() => {
        showMessage('Some message');
      }).not.toThrow();

      expect(warnSpy).toHaveBeenCalledWith('payment-message container not found');
    });
  });

  describe('setLoading', () => {
    test('adds is-loading class when isLoading is true', () => {
      const block = jQuery('#payment-block');
      setLoading(true);
      expect(block.hasClass('is-loading')).toBe(true);
    });

    test('removes is-loading class when isLoading is false', () => {
      const block = jQuery('#payment-block');
      block.addClass('is-loading');
      setLoading(false);
      expect(block.hasClass('is-loading')).toBe(false);
    });
  });
});
