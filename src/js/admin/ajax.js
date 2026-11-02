/**
 * Admin AJAX entrypoint: sets up global Racketmanager methods and registers
 * dropdown cascade delegation for meta boxes and admin pages.
 *
 * @module admin/ajax
 */

import {
    getEventDropdown,
    getLeagueDropdown,
    getSeasonDropdown,
    getMatchDropdown,
    registerDropdownCascades,
} from './features/dropdowns/index.js';

import {
    getImportOption,
    setEventName,
    amendDate,
    setEndDate,
    setNumMatchDays,
    setTournamentOpenDate,
    sendFixtures,
    notifyTournamentEntryOpen,
    notify_open,
    emailConstitution,
    notifyTeams,
    insertHomeStadium,
    setMatchDate,
    setMatchDayPopUp,
    saveAddPoints,
} from './features/ajax/index.js';

window.Racketmanager = window.Racketmanager || {};
const Racketmanager = window.Racketmanager;

Racketmanager.getEventDropdown = getEventDropdown;
Racketmanager.getLeagueDropdown = getLeagueDropdown;
Racketmanager.getSeasonDropdown = getSeasonDropdown;
Racketmanager.getMatchDropdown = getMatchDropdown;
Racketmanager.getImportOption = getImportOption;
Racketmanager.setEventName = setEventName;
Racketmanager.setEndDate = setEndDate;
Racketmanager.amendDate = amendDate;
Racketmanager.setNumMatchDays = setNumMatchDays;
Racketmanager.setTournamentOpenDate = setTournamentOpenDate;
Racketmanager.sendFixtures = sendFixtures;
Racketmanager.notifyTournamentEntryOpen = notifyTournamentEntryOpen;
Racketmanager.notify_open = notify_open;
Racketmanager.emailConstitution = emailConstitution;
Racketmanager.notifyTeams = notifyTeams;
Racketmanager.insertHomeStadium = insertHomeStadium;
Racketmanager.setMatchDate = setMatchDate;
Racketmanager.setMatchDayPopUp = setMatchDayPopUp;
Racketmanager.saveAddPoints = saveAddPoints;

if (typeof document !== 'undefined') {
    registerDropdownCascades(document);
}
