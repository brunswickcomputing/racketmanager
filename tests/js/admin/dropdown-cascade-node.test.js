/**
 * @jest-environment node
 */

import {
  getEventDropdown,
  getLeagueDropdown,
  getSeasonDropdown,
  getMatchDropdown,
  registerDropdownCascades,
} from '../../../src/js/admin/features/dropdowns/dropdown-cascade.js';

describe('Dropdown cascade in Node environment (SSR / non-window guards)', () => {
  it('safely handles non-window execution when window is undefined', () => {
    expect(typeof window).toBe('undefined');

    const mockElements = {
      '#events': { style: {}, classList: { remove: jest.fn(), add: jest.fn() } },
      '#leagues': { style: {}, classList: { remove: jest.fn(), add: jest.fn() } },
      '#seasons': { style: {}, classList: { remove: jest.fn(), add: jest.fn() } },
      '#matches': { style: {}, classList: { remove: jest.fn(), add: jest.fn() } },
    };

    global.document = {
      querySelector: (selector) => mockElements[selector] || null,
    };

    try {
      expect(() => getEventDropdown(10)).not.toThrow();
      expect(mockElements['#leagues'].style.display).toBe('none');
      expect(mockElements['#seasons'].style.display).toBe('none');
      expect(mockElements['#events'].classList.remove).toHaveBeenCalledWith('message-error');

      expect(() => getLeagueDropdown(20)).not.toThrow();
      expect(mockElements['#seasons'].style.display).toBe('none');
      expect(mockElements['#leagues'].classList.remove).toHaveBeenCalledWith('message-error');

      expect(() => getSeasonDropdown(30)).not.toThrow();
      expect(mockElements['#seasons'].classList.remove).toHaveBeenCalledWith('message-error');

      expect(() => getMatchDropdown(40, 2024)).not.toThrow();
      expect(mockElements['#matches'].classList.remove).toHaveBeenCalledWith('message-error');

      expect(() => registerDropdownCascades(null)).not.toThrow();
      expect(() => registerDropdownCascades({})).not.toThrow();
    } finally {
      delete global.document;
    }
  });
});
