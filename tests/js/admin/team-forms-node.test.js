/**
 * @jest-environment node
 */

import { getPlayerDetails, initTeamAutocomplete } from '../../../src/js/admin/features/forms/team-autocomplete.js';
import { validateTeamPlayerForm } from '../../../src/js/admin/features/forms/team-validation.js';

describe('Admin team forms in Node environment (SSR / non-DOM guards)', () => {
  describe('team-autocomplete in Node', () => {
    it('getPlayerDetails returns null when window is undefined', () => {
      expect(typeof window).toBe('undefined');
      expect(getPlayerDetails('Smith')).toBeNull();
    });

    it('initTeamAutocomplete returns early when document is undefined', () => {
      expect(typeof document).toBe('undefined');
      expect(() => initTeamAutocomplete()).not.toThrow();
    });
  });

  describe('team-validation in Node', () => {
    it('validateTeamPlayerForm handles undefined window in duplicate check branch', () => {
      expect(typeof window).toBe('undefined');
      const mockElements = {
        team: { value: 'New Team' },
        team_id: { value: '' },
        teamPlayer1: { value: 'Player One' },
        clubId: { value: '10' },
      };
      global.document = {
        getElementById: (id) => mockElements[id] || null,
      };

      try {
        const preventDefault = jest.fn();
        const result = validateTeamPlayerForm({ preventDefault });
        expect(result).toBe(true);
        expect(preventDefault).not.toHaveBeenCalled();
      } finally {
        delete global.document;
      }
    });
  });
});
