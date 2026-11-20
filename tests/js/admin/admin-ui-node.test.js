/**
 * @jest-environment node
 */

import {
  showPlayerClubs,
  initTooltips,
  initPopovers,
  initColorpicker,
  initGoTop,
  initMiscellaneousUI,
} from '../../../src/js/admin/features/ui/admin-ui.js';

describe('Admin UI in Node environment (SSR / non-DOM guards)', () => {
  it('handles undefined window and document safely across all UI methods', () => {
    expect(typeof window).toBe('undefined');
    expect(typeof document).toBe('undefined');

    expect(showPlayerClubs(null)).toBe('');
    expect(() => initTooltips()).not.toThrow();
    expect(() => initPopovers()).not.toThrow();
    expect(() => initColorpicker()).not.toThrow();
    expect(() => initGoTop()).not.toThrow();
    expect(() => initMiscellaneousUI()).not.toThrow();
  });
});
