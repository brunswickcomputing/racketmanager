/**
 * @jest-environment jsdom
 */

import {
  handleAjaxError,
  extractMessage,
  applyFieldValidation,
  initializeAjaxError,
} from '../../../../src/js/features/ajax/handle-ajax-error.js';
import { logger, recordEvent } from '../../../../src/js/utils/logger.js';

jest.mock('../../../../src/js/utils/logger.js', () => ({
  logger: {
    error: jest.fn(),
  },
  recordEvent: jest.fn(),
}));

describe('features/ajax/handle-ajax-error', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="testAlert" class="alert">
        <div id="testAlertResponse"></div>
      </div>
      <form id="testForm">
        <input type="text" id="username" />
        <div id="usernameFeedback"></div>
        <input type="text" id="email" />
        <div id="emailFeedback"></div>
        <input type="text" id="phone" />
      </form>
    `;
    jest.clearAllMocks();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('extractMessage', () => {
    test('extracts msg string when response.responseJSON.data.msg is present', () => {
      const response = {
        responseJSON: {
          data: {
            msg: 'Specific error occurred',
          },
        },
      };
      expect(extractMessage(response)).toBe('Specific error occurred');
    });

    test('extracts message string when response.responseJSON.data.message is present', () => {
      const response = {
        responseJSON: {
          data: {
            message: 'Validation failed on server',
          },
        },
      };
      expect(extractMessage(response)).toBe('Validation failed on server');
    });

    test('extracts raw string when data is a string', () => {
      const response = {
        responseJSON: {
          data: 'Direct string error',
        },
      };
      expect(extractMessage(response)).toBe('Direct string error');
    });

    test('stringifies object data when neither msg nor message is a string', () => {
      const response = {
        responseJSON: {
          data: {
            code: 404,
            detail: 'Not found',
          },
        },
      };
      expect(extractMessage(response)).toBe(JSON.stringify({ code: 404, detail: 'Not found' }));
    });

    test('falls back to statusText when JSON stringify throws', () => {
      const circular = {};
      circular.self = circular;
      const response = {
        statusText: 'Circular Error',
        responseJSON: {
          data: circular,
        },
      };
      expect(extractMessage(response)).toBe('Circular Error');
    });

    test('falls back to statusText when responseJSON is absent', () => {
      const response = {
        statusText: 'Internal Server Error',
      };
      expect(extractMessage(response)).toBe('Internal Server Error');
    });

    test('returns default "Request failed" when response is null or undefined or empty', () => {
      expect(extractMessage(null)).toBe('Request failed');
      expect(extractMessage(undefined)).toBe('Request failed');
      expect(extractMessage({})).toBe('Request failed');
    });
  });

  describe('applyFieldValidation', () => {
    test('returns empty array when data or arrays are missing or non-objects', () => {
      expect(applyFieldValidation(null)).toEqual([]);
      expect(applyFieldValidation(undefined)).toEqual([]);
      expect(applyFieldValidation('not-an-object')).toEqual([]);
      expect(applyFieldValidation(123)).toEqual([]);
      expect(applyFieldValidation({})).toEqual([]);
      expect(applyFieldValidation({ err_flds: 'username' })).toEqual([]);
      expect(applyFieldValidation({ err_msgs: ['Error'] })).toEqual([]);
    });

    test('applies is-invalid class and sets innerHTML on feedback element when present', () => {
      const data = {
        err_flds: ['username', 'email'],
        err_msgs: ['Username is required', 'Invalid email address'],
      };

      const extra = applyFieldValidation(data);

      expect(extra).toEqual([]);
      expect(jQuery('#username').hasClass('is-invalid')).toBe(true);
      expect(jQuery('#usernameFeedback').html()).toBe('Username is required');
      expect(jQuery('#email').hasClass('is-invalid')).toBe(true);
      expect(jQuery('#emailFeedback').html()).toBe('Invalid email address');
    });

    test('appends message to extraMessages when feedback element is missing', () => {
      const data = {
        err_flds: ['phone'],
        err_msgs: ['Phone number is invalid'],
      };

      const extra = applyFieldValidation(data);

      expect(jQuery('#phone').hasClass('is-invalid')).toBe(true);
      expect(extra).toEqual(['Phone number is invalid']);
    });

    test('does not append empty or missing message to extraMessages when feedback element is missing', () => {
      const data = {
        err_flds: ['phone'],
        err_msgs: [''],
      };

      const extra = applyFieldValidation(data);

      expect(jQuery('#phone').hasClass('is-invalid')).toBe(true);
      expect(extra).toEqual([]);
    });

    test('handles mismatched err_flds and err_msgs arrays gracefully', () => {
      const data = {
        err_flds: ['username', 'phone', 'nonExistent'],
        err_msgs: ['Username error'], // only 1 message for 3 fields
      };

      const extra = applyFieldValidation(data);

      expect(jQuery('#username').hasClass('is-invalid')).toBe(true);
      expect(jQuery('#usernameFeedback').html()).toBe('Username error');
      expect(jQuery('#phone').hasClass('is-invalid')).toBe(true);
      expect(extra).toEqual([]); // phone has undefined message, so not added to extra
    });
  });

  describe('handleAjaxError', () => {
    test('renders primary message and adds alert--danger class to alert container', () => {
      const response = {
        status: 500,
        statusText: 'Server Error',
        responseJSON: {
          data: {
            msg: 'Database connection failed',
          },
        },
      };

      handleAjaxError(response, '#testAlertResponse', '#testAlert');

      expect(jQuery('#testAlert').hasClass('alert--danger')).toBe(true);
      expect(jQuery('#testAlertResponse').html()).toBe('Database connection failed');
      expect(logger.error).toHaveBeenCalledWith('AJAX error', expect.objectContaining({
        status: 500,
        statusText: 'Server Error',
      }));
      expect(recordEvent).toHaveBeenCalledWith('ajax_error', { status: 500 });
    });

    test('preserves message order: primary message first, then unplaced field messages joined with <br>', () => {
      const response = {
        status: 400,
        statusText: 'Bad Request',
        responseJSON: {
          data: {
            msg: 'Form submission failed',
            err_flds: ['username', 'phone', 'secondaryPhone'],
            err_msgs: ['Username taken', 'Invalid phone', 'Invalid secondary phone'],
          },
        },
      };

      handleAjaxError(response, '#testAlertResponse', '#testAlert');

      // username has #usernameFeedback
      expect(jQuery('#username').hasClass('is-invalid')).toBe(true);
      expect(jQuery('#usernameFeedback').html()).toBe('Username taken');

      // phone and secondaryPhone have no feedback element
      expect(jQuery('#phone').hasClass('is-invalid')).toBe(true);
      expect(jQuery('#testAlert').hasClass('alert--danger')).toBe(true);
      expect(jQuery('#testAlertResponse').html()).toBe(
        'Form submission failed<br>Invalid phone<br>Invalid secondary phone'
      );
    });

    test('handles non-object responseJSON data and still renders message and alert--danger', () => {
      const response = {
        status: 403,
        statusText: 'Forbidden',
        responseJSON: {
          data: 'You are not allowed to perform this action',
        },
      };

      handleAjaxError(response, '#testAlertResponse', '#testAlert');

      expect(jQuery('#testAlert').hasClass('alert--danger')).toBe(true);
      expect(jQuery('#testAlertResponse').html()).toBe('You are not allowed to perform this action');
    });

    test('handles logger or recordEvent failure gracefully without throwing', () => {
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
      logger.error.mockImplementationOnce(() => {
        throw new Error('Logger failure');
      });

      const response = { status: 500, statusText: 'Error' };
      expect(() => handleAjaxError(response, '#testAlertResponse', '#testAlert')).not.toThrow();
      expect(jQuery('#testAlert').hasClass('alert--danger')).toBe(true);
      consoleErrorSpy.mockRestore();
    });
  });

  describe('initializeAjaxError', () => {
    test('binds handleAjaxError to globalThis.Racketmanager.handleAjaxError', () => {
      const previousRM = globalThis.Racketmanager;
      try {
        globalThis.Racketmanager = {};
        initializeAjaxError();
        expect(globalThis.Racketmanager.handleAjaxError).toBe(handleAjaxError);
      } finally {
        globalThis.Racketmanager = previousRM;
      }
    });

    test('initializes globalThis.Racketmanager if it is not defined', () => {
      const previousRM = globalThis.Racketmanager;
      try {
        delete globalThis.Racketmanager;
        initializeAjaxError();
        expect(globalThis.Racketmanager).toBeDefined();
        expect(globalThis.Racketmanager.handleAjaxError).toBe(handleAjaxError);
      } finally {
        globalThis.Racketmanager = previousRM;
      }
    });
  });
});
