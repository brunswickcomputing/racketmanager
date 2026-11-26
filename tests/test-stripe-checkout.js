/**
 * Regression Test Suite for Stripe Checkout (js/stripe-checkout.js)
 * 
 * Verifies on-demand payment element loading, initial state, click handlers,
 * real createPaymentRequest integration, error states, and legacy fallbacks.
 * 
 * Run with: node tests/test-stripe-checkout.js
 */

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const STRIPE_CHECKOUT_PATH = path.join(__dirname, '..', 'js', 'stripe-checkout.js');
const RACKETMANAGER_PATH = path.join(__dirname, '..', 'js', 'racketmanager.js');

const stripeCheckoutCode = fs.readFileSync(STRIPE_CHECKOUT_PATH, 'utf8');
const racketmanagerCode = fs.readFileSync(RACKETMANAGER_PATH, 'utf8');

// Extract real createPaymentRequest from js/racketmanager.js
const createPaymentRequestMatch = racketmanagerCode.match(/function\s+createPaymentRequest\s*\([^)]*\)\s*\{[\s\S]*?\n\}/);
if (!createPaymentRequestMatch) {
  throw new Error('Failed to extract createPaymentRequest from js/racketmanager.js');
}
const realCreatePaymentRequestCode = createPaymentRequestMatch[0];

/**
 * Creates a lightweight DOM mock environment matching templates/entry/tournament-payment.php
 */
function createMockEnvironment(options = {}) {
  const elements = new Map();

  class MockElement {
    constructor(id, tagName = 'div', parent = null) {
      this.id = id;
      this.tagName = tagName.toUpperCase();
      this.parentNode = parent;
      this.children = [];
      this.style = { display: '' };
      this.value = '';
      this.textContent = '';
      this._classList = new Set();
      this.listeners = {};
    }

    get classList() {
      const self = this;
      return {
        add(...cls) { cls.forEach(c => self._classList.add(c)); },
        remove(...cls) { cls.forEach(c => self._classList.delete(c)); },
        contains(c) { return self._classList.has(c); }
      };
    }

    addEventListener(event, fn) {
      if (!this.listeners[event]) {
        this.listeners[event] = [];
      }
      this.listeners[event].push(fn);
    }

    click() {
      const ev = {
        preventDefault: () => {},
        stopPropagation: () => {},
        target: this
      };
      const handlers = this.listeners['click'] || [];
      for (const h of handlers) {
        h.call(this, ev);
      }
    }

    contains(child) {
      if (child === this) return true;
      let curr = child ? child.parentNode : null;
      while (curr) {
        if (curr === this) return true;
        curr = curr.parentNode;
      }
      return false;
    }

    appendChild(child) {
      if (child.parentNode) {
        child.parentNode.removeChild(child);
      }
      child.parentNode = this;
      this.children.push(child);
      return child;
    }

    insertBefore(newNode, refNode) {
      if (newNode.parentNode) {
        newNode.parentNode.removeChild(newNode);
      }
      const idx = this.children.indexOf(refNode);
      if (idx === -1) {
        return this.appendChild(newNode);
      }
      newNode.parentNode = this;
      this.children.splice(idx, 0, newNode);
      return newNode;
    }

    removeChild(child) {
      const idx = this.children.indexOf(child);
      if (idx !== -1) {
        this.children.splice(idx, 1);
        child.parentNode = null;
      }
      return child;
    }
  }

  const root = new MockElement('root', 'div');

  function addEl(id, tagName = 'div', parent = root) {
    const el = new MockElement(id, tagName, parent);
    elements.set(id, el);
    parent.children.push(el);
    return el;
  }

  // Setup initial DOM elements from tournament-payment.php
  const apiKeyEl = addEl('api_publishable_key', 'input');
  apiKeyEl.value = options.publishableKey !== undefined ? options.publishableKey : 'pk_test_mock_12345';

  const entryIdEl = addEl('tournamentEntryId', 'input');
  entryIdEl.value = '42';

  const invoiceIdEl = addEl('invoiceId', 'input');
  invoiceIdEl.value = '101';

  const playerNameEl = addEl('playerName', 'input');
  playerNameEl.value = 'John Doe';

  const playerEmailEl = addEl('playerEmail', 'input');
  playerEmailEl.value = 'john@example.com';

  const playerContactNoEl = addEl('playerContactNo', 'input');
  playerContactNoEl.value = '07123456789';

  const paymentCompleteUrlEl = addEl('paymentCompleteUrl', 'input');
  paymentCompleteUrlEl.value = 'https://example.com/payment-complete/';

  let checkoutCcBtn = null;
  if (options.includeButton !== false) {
    checkoutCcBtn = addEl('checkout-cc-button', 'button');
    checkoutCcBtn.style.display = '';
  }

  const paymentForm = addEl('payment-form', 'form');
  paymentForm.style.display = 'none';

  const paymentElement = addEl('payment-element', 'div', paymentForm);
  const paymentBlock = addEl('payment-block', 'div', paymentForm);
  const submitBtn = addEl('submit', 'button', paymentBlock);
  const paymentMessage = addEl('payment-message', 'div', paymentForm);
  paymentMessage.classList.add('hidden');

  const documentMock = {
    getElementById(id) {
      return elements.get(id) || null;
    },
    querySelector(selector) {
      if (selector.startsWith('#')) {
        return elements.get(selector.slice(1)) || null;
      }
      return null;
    },
    querySelectorAll(selector) {
      const el = this.querySelector(selector);
      return el ? [el] : [];
    }
  };

  const ajaxCalls = [];
  let ajaxHandler = options.ajaxHandler || function(settings) {
    // default success
    if (settings.success) {
      settings.success({ data: 'pi_3MtwBwLkdIwHu7ix28a3tqPa_secret_YrKJUKribcBjcG8HVhfZluoGH' });
    }
    if (settings.complete) {
      settings.complete();
    }
  };

  function jQueryMock(selector) {
    if (selector && selector[0] instanceof MockElement) {
      selector = selector[0];
    }
    if (selector instanceof MockElement) {
      const el = selector;
      return {
        0: el,
        val() { return el.value; },
        show() { el.style.display = 'block'; return this; },
        hide() { el.style.display = 'none'; return this; },
        empty() {
          el.children.forEach(c => { c.parentNode = null; });
          el.children = [];
          el.textContent = '';
          return this;
        },
        is(cond) {
          if (cond === ':hidden') return el.style.display === 'none';
          return false;
        },
        addClass(cls) { el.classList.add(cls); return this; },
        removeClass(cls) { el.classList.remove(cls); return this; }
      };
    }

    if (typeof selector === 'string' && selector.startsWith('#')) {
      const el = elements.get(selector.slice(1));
      return {
        0: el,
        val() { return el ? el.value : ''; },
        show() { if (el) el.style.display = 'block'; return this; },
        hide() { if (el) el.style.display = 'none'; return this; },
        empty() {
          if (el) {
            el.children.forEach(c => { c.parentNode = null; });
            el.children = [];
            el.textContent = '';
          }
          return this;
        },
        is(cond) {
          if (!el) return false;
          if (cond === ':hidden') return el.style.display === 'none';
          return false;
        },
        addClass(cls) { if (el) el.classList.add(cls); return this; },
        removeClass(cls) { if (el) el.classList.remove(cls); return this; }
      };
    }

    return {
      0: null,
      val() { return ''; },
      show() { return this; },
      hide() { return this; },
      empty() { return this; },
      is() { return false; },
      addClass() { return this; },
      removeClass() { return this; }
    };
  }

  jQueryMock.ajax = function(settings) {
    ajaxCalls.push(settings);
    if (ajaxHandler) {
      ajaxHandler(settings);
    }
  };

  // Stripe mock
  const stripeCalls = {
    instantiations: [],
    elementsOptions: [],
    elementCreate: [],
    elementMount: [],
    destroyedCount: 0
  };

  let mockPaymentElement = {
    on(event, handler) {
      this.loadErrorHandler = handler;
    },
    mount(target) {
      stripeCalls.elementMount.push(target);
      if (options.mountThrows) {
        throw new Error('Mount failed');
      }
    },
    destroy() {
      stripeCalls.destroyedCount++;
    }
  };

  let mockElements = {
    create(type, opts) {
      stripeCalls.elementCreate.push({ type, opts });
      return mockPaymentElement;
    },
    getElement(type) {
      return mockPaymentElement;
    }
  };

  let stripeConstructor = function(key) {
    if (options.stripeConstructorThrows) {
      throw new Error('Stripe constructor failed');
    }
    stripeCalls.instantiations.push(key);
    return {
      elements(opts) {
        stripeCalls.elementsOptions.push(opts);
        if (options.elementsThrows) {
          throw new Error('Stripe elements creation failed');
        }
        return mockElements;
      },
      confirmPayment: async () => ({})
    };
  };

  const context = {
    document: documentMock,
    jQuery: jQueryMock,
    Stripe: options.stripeFunction !== undefined ? options.stripeFunction : stripeConstructor,
    ajax_var: {
      url: '/wp-admin/admin-ajax.php',
      ajax_nonce: 'mock-nonce-123'
    },
    console: {
      log: () => {},
      error: () => {},
      warn: () => {}
    },
    setTimeout: (fn, delay) => {
      const t = setTimeout(fn, 0);
      if (t && typeof t.unref === 'function') {
        t.unref();
      }
      return t;
    },
    clearTimeout: (id) => clearTimeout(id)
  };

  vm.createContext(context);

  return {
    context,
    elements,
    ajaxCalls,
    stripeCalls,
    mockPaymentElement,
    setAjaxHandler(fn) {
      ajaxHandler = fn;
    },
    runScript(code = stripeCheckoutCode) {
      // Pre-load createPaymentRequest unless disabled
      if (options.includePaymentRequest !== false) {
        vm.runInContext(realCreatePaymentRequestCode, context);
      }
      vm.runInContext(code, context);
    }
  };
}

// ---------------------- TEST SUITE ----------------------

test('1. Initial state: button visible, payment-form hidden, no AJAX request sent on script load', () => {
  const env = createMockEnvironment();
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');

  assert.strictEqual(checkoutBtn.style.display, '', 'Pay by card button must be visible initially');
  assert.strictEqual(paymentForm.style.display, 'none', 'Payment form must be hidden initially');
  assert.strictEqual(env.ajaxCalls.length, 0, 'No AJAX request should be sent on initial script load');
});

test('2. Single click: button immediately hides, triggers createPaymentRequest with correct parameters', () => {
  const env = createMockEnvironment();
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, 'none', 'Pay by card button should be hidden immediately on click');
  assert.strictEqual(env.ajaxCalls.length, 1, 'Exactly one AJAX request should be dispatched');

  const ajax = env.ajaxCalls[0];
  assert.strictEqual(ajax.url, '/wp-admin/admin-ajax.php');
  assert.strictEqual(ajax.type, 'POST');
  assert.strictEqual(ajax.data.action, 'racketmanager_tournament_payment_create');
  assert.strictEqual(ajax.data.security, 'mock-nonce-123');
  assert.strictEqual(ajax.data.tournament_entry, '42');
  assert.strictEqual(ajax.data.invoiceId, '101');
});

test('3. Duplicate click guard: rapid clicking while request in flight does not trigger duplicate AJAX', () => {
  let pendingCallback;
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      // Keep request in-flight
      pendingCallback = () => {
        settings.success({ data: 'pi_3MtwBwLkdIwHu7ix28a3tqPa_secret_YrKJUKribcBjcG8HVhfZluoGH' });
        settings.complete();
      };
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  checkoutBtn.click();
  assert.strictEqual(env.ajaxCalls.length, 1);

  // Rapid synthetic clicks
  checkoutBtn.click();
  checkoutBtn.click();
  assert.strictEqual(env.ajaxCalls.length, 1, 'In-flight request guard must ignore subsequent clicks');

  // Complete first request
  pendingCallback();

  const paymentForm = env.elements.get('payment-form');
  assert.strictEqual(paymentForm.style.display, 'block', 'Payment form should be displayed after completion');

  // Attempt click after form is displayed
  checkoutBtn.click();
  assert.strictEqual(env.ajaxCalls.length, 1, 'Clicks when form is visible must be ignored');
});

test('4. Successful payment intent: mounts Stripe Payment Element and shows payment form', () => {
  const env = createMockEnvironment();
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  checkoutBtn.click();

  assert.strictEqual(env.stripeCalls.elementsOptions.length, 1);
  assert.strictEqual(env.stripeCalls.elementsOptions[0].clientSecret, 'pi_3MtwBwLkdIwHu7ix28a3tqPa_secret_YrKJUKribcBjcG8HVhfZluoGH');

  assert.strictEqual(env.stripeCalls.elementMount.length, 1);
  assert.strictEqual(env.stripeCalls.elementMount[0], '#payment-element');

  const paymentForm = env.elements.get('payment-form');
  assert.strictEqual(paymentForm.style.display, 'block', 'Payment form must be displayed on success');
  assert.strictEqual(checkoutBtn.style.display, 'none', 'Pay by card button remains hidden on success');

  const paymentMessage = env.elements.get('payment-message');
  assert.strictEqual(paymentForm.contains(paymentMessage), true, 'Payment message container restored inside form');
});

test('5. Server error (string error message): displays error, keeps form hidden, and restores button for retry', () => {
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      if (settings.error) {
        settings.error({
          statusText: 'Internal Server Error',
          responseJSON: { data: 'Tournament entry not found' }
        });
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(paymentForm.style.display, 'none', 'Payment form remains hidden on error');
  assert.strictEqual(checkoutBtn.style.display, '', 'Pay by card button is restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'Tournament entry not found');
  assert.strictEqual(paymentMessage.style.display, 'block');
  assert.strictEqual(paymentForm.contains(paymentMessage), false, 'Message moved outside hidden form for visibility');
});

test('6. Server error (JSON object with error property): displays error text and restores button', () => {
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      if (settings.error) {
        settings.error({
          statusText: 'Internal Server Error',
          responseJSON: { data: { error: 'Your card is not supported in this region.' } }
        });
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'Your card is not supported in this region.');
});

test('7. Server error (JSON object with message property): displays message text and restores button', () => {
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      if (settings.error) {
        settings.error({
          statusText: 'Internal Server Error',
          responseJSON: { data: { message: 'Invoice has already been settled.' } }
        });
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'Invoice has already been settled.');
});

test('8. Network failure (non-JSON 500 error): displays HTTP statusText and restores button', () => {
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      if (settings.error) {
        settings.error({
          statusText: 'Gateway Timeout',
          responseJSON: null
        });
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'Gateway Timeout');
});

test('9. Invalid client secret format: bare pi_ ID rejected, displays error, and restores button', () => {
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      // Returns bare PaymentIntent ID without _secret_
      if (settings.success) {
        settings.success({ data: 'pi_3MtwBwLkdIwHu7ix28a3tqPa' });
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(env.stripeCalls.elementsOptions.length, 0, 'Elements should not be created for bare pi_ ID');
  assert.strictEqual(paymentForm.style.display, 'none');
  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'pi_3MtwBwLkdIwHu7ix28a3tqPa');
});

test('10. Retry flow: user can click restored button after error and proceed successfully', () => {
  let attempt = 0;
  const env = createMockEnvironment({
    ajaxHandler: (settings) => {
      attempt++;
      if (attempt === 1) {
        if (settings.error) {
          settings.error({
            statusText: 'Error',
            responseJSON: { data: 'Temporary backend error' }
          });
        }
      } else {
        if (settings.success) {
          settings.success({ data: 'pi_3MtwBwLkdIwHu7ix28a3tqPa_secret_YrKJUKribcBjcG8HVhfZluoGH' });
        }
      }
      if (settings.complete) settings.complete();
    }
  });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');
  const paymentMessage = env.elements.get('payment-message');

  // Attempt 1: Fails
  checkoutBtn.click();
  assert.strictEqual(attempt, 1);
  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored after first failure');
  assert.strictEqual(paymentForm.style.display, 'none');
  assert.strictEqual(paymentMessage.textContent, 'Temporary backend error');

  // Attempt 2: Succeeds
  checkoutBtn.click();
  assert.strictEqual(attempt, 2);
  assert.strictEqual(checkoutBtn.style.display, 'none', 'Button hidden after retry click');
  assert.strictEqual(paymentForm.style.display, 'block', 'Payment form displayed on successful retry');
});

test('11. Stripe initialization failure: missing API publishable key', () => {
  const env = createMockEnvironment({ publishableKey: '' });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored');
  assert.strictEqual(paymentMessage.textContent, 'Payment system failed to initialize.');
  assert.strictEqual(env.ajaxCalls.length, 0, 'No AJAX sent if Stripe initialization fails');
});

test('12. Stripe initialization failure: Stripe SDK function unavailable', () => {
  const env = createMockEnvironment({ stripeFunction: null });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored');
  assert.strictEqual(paymentMessage.textContent, 'Payment system failed to initialize.');
  assert.strictEqual(env.ajaxCalls.length, 0);
});

test('13. Stripe constructor throws exception', () => {
  const env = createMockEnvironment({ stripeConstructorThrows: true });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored');
  assert.strictEqual(paymentMessage.textContent, 'Payment system failed to initialize.');
});

test('14. Missing createPaymentRequest service function', () => {
  const env = createMockEnvironment({ includePaymentRequest: false });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored');
  assert.strictEqual(paymentMessage.textContent, 'Payment service is unavailable.');
});

test('15. Error during element mount: catches exception, hides form, restores button', () => {
  const env = createMockEnvironment({ mountThrows: true });
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(paymentForm.style.display, 'none', 'Form must be hidden on mount error');
  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored for retry');
  assert.strictEqual(paymentMessage.textContent, 'Mount failed');
});

test('16. Stripe Payment Element loaderror event: hides form and restores button', () => {
  const env = createMockEnvironment();
  env.runScript();

  const checkoutBtn = env.elements.get('checkout-cc-button');
  const paymentForm = env.elements.get('payment-form');
  const paymentMessage = env.elements.get('payment-message');

  checkoutBtn.click();

  assert.strictEqual(paymentForm.style.display, 'block', 'Form initially displayed');

  // Simulate Stripe loaderror event
  assert.ok(env.mockPaymentElement.loadErrorHandler, 'loaderror handler must be registered');
  env.mockPaymentElement.loadErrorHandler({
    error: { message: 'Failed to load Stripe payment element styles.' }
  });

  assert.strictEqual(paymentForm.style.display, 'none', 'Form hidden on loaderror');
  assert.strictEqual(checkoutBtn.style.display, '', 'Button restored on loaderror');
  assert.strictEqual(paymentMessage.textContent, 'Failed to load Stripe payment element styles.');
});

test('17. Legacy fallback: when #checkout-cc-button is not present, initializes on load', () => {
  const env = createMockEnvironment({ includeButton: false });
  env.runScript();

  assert.strictEqual(env.ajaxCalls.length, 1, 'AJAX request should fire immediately on load when button is absent');

  const paymentForm = env.elements.get('payment-form');
  assert.strictEqual(paymentForm.style.display, 'block', 'Payment form displayed upon receiving client secret');
  assert.strictEqual(env.stripeCalls.elementMount.length, 1);
});
