/**
 * Tests for admin feature barrel exports.
 */
import * as ajaxBarrel from '../../../src/js/admin/features/ajax/index.js';
import * as draggableBarrel from '../../../src/js/admin/features/draggable/index.js';
import * as dropdownsBarrel from '../../../src/js/admin/features/dropdowns/index.js';
import * as formsBarrel from '../../../src/js/admin/features/forms/index.js';
import * as uiBarrel from '../../../src/js/admin/features/ui/index.js';

describe('Admin feature barrel exports', () => {
  it('re-exports ajax features', () => {
    expect(typeof ajaxBarrel.getImportOption).toBe('function');
    expect(typeof ajaxBarrel.setEventName).toBe('function');
    expect(typeof ajaxBarrel.amendDate).toBe('function');
    expect(typeof ajaxBarrel.setEndDate).toBe('function');
    expect(typeof ajaxBarrel.setNumMatchDays).toBe('function');
    expect(typeof ajaxBarrel.setTournamentOpenDate).toBe('function');
    expect(typeof ajaxBarrel.sendFixtures).toBe('function');
    expect(typeof ajaxBarrel.notifyTournamentEntryOpen).toBe('function');
    expect(typeof ajaxBarrel.notify_open).toBe('function');
    expect(typeof ajaxBarrel.emailConstitution).toBe('function');
    expect(typeof ajaxBarrel.notifyTeams).toBe('function');
    expect(typeof ajaxBarrel.insertHomeStadium).toBe('function');
    expect(typeof ajaxBarrel.setMatchDate).toBe('function');
    expect(typeof ajaxBarrel.setMatchDayPopUp).toBe('function');
    expect(typeof ajaxBarrel.saveAddPoints).toBe('function');
  });

  it('re-exports draggable features', () => {
    expect(typeof draggableBarrel.dragStart).toBe('function');
    expect(typeof draggableBarrel.dragEnd).toBe('function');
    expect(typeof draggableBarrel.dragEnter).toBe('function');
    expect(typeof draggableBarrel.dragOver).toBe('function');
    expect(typeof draggableBarrel.dragLeave).toBe('function');
    expect(typeof draggableBarrel.drop).toBe('function');
    expect(typeof draggableBarrel.restoreMatchSchedule).toBe('function');
    expect(typeof draggableBarrel.initMatchDraggable).toBe('function');
  });

  it('re-exports dropdown features', () => {
    expect(typeof dropdownsBarrel.getEventDropdown).toBe('function');
    expect(typeof dropdownsBarrel.getLeagueDropdown).toBe('function');
    expect(typeof dropdownsBarrel.getSeasonDropdown).toBe('function');
    expect(typeof dropdownsBarrel.getMatchDropdown).toBe('function');
    expect(typeof dropdownsBarrel.registerDropdownCascades).toBe('function');
  });

  it('re-exports forms features', () => {
    expect(typeof formsBarrel.validateTeamPlayerForm).toBe('function');
    expect(typeof formsBarrel.initTeamValidation).toBe('function');
    expect(typeof formsBarrel.getPlayerDetails).toBe('function');
    expect(typeof formsBarrel.initTeamAutocomplete).toBe('function');
  });

  it('re-exports ui features', () => {
    expect(typeof uiBarrel.initAdminUI).toBe('function');
  });
});
