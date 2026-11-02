/**
 * Shared jQuery stub for admin feature tests.
 *
 * Provides one chainable object so delay/fadeOut/animate calls can be asserted,
 * plus a jQuery.ajax mock that records request options.
 */
export function createJQueryStub() {
  const chain = {};
  chain.delay = jest.fn(() => chain);
  chain.fadeOut = jest.fn(() => chain);
  chain.animate = jest.fn(() => chain);

  const jq = jest.fn(() => chain);
  jq.fn = { animate: jest.fn(), tooltip: undefined, popover: undefined, iris: undefined, sortable: undefined };
  jq.ajax = jest.fn();

  return { jq, chain, delay: chain.delay, fadeOut: chain.fadeOut, animate: chain.animate };
}

/**
 * Make the next jQuery.ajax call invoke its success callback synchronously.
 */
export function respondWithSuccess(jq, payload) {
  jq.ajax.mockImplementation((opts) => {
    opts.success(payload);
  });
}

/**
 * Make the next jQuery.ajax call invoke its error callback synchronously.
 */
export function respondWithError(jq, xhr) {
  jq.ajax.mockImplementation((opts) => {
    opts.error(xhr);
  });
}
