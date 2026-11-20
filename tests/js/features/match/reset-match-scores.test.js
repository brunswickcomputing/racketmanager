/**
 * @jest-environment jsdom
 */

import { resetScoresInContainer, resetMatchScoresByFormId, initializeResetMatchScores } from '../../../../src/js/features/match/reset-match-scores.js';
import * as setCalculatorModule from '../../../../src/js/features/match/set-calculator.js';

describe('features/match/reset-match-scores', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.jQuery = window.$ = require('jquery');
    });

    test('returns early if container does not exist or formId is empty', () => {
        resetScoresInContainer('#non-existent');
        resetMatchScoresByFormId('');
        resetMatchScoresByFormId(null);
    });

    test('resets various input types and triggers SetCalculator for player inputs', () => {
        const spyCalc = jest.spyOn(setCalculatorModule, 'SetCalculator').mockImplementation(() => {});

        document.body.innerHTML = `
            <div id="testContainer">
                <input type="text" id="home_player_1" value="Player Name" />
                <input type="number" id="score_1" value="6" />
                <select id="sel_1">
                    <option value="1" selected>Opt 1</option>
                    <option value="0">None</option>
                </select>
                <input type="checkbox" id="chk_1" checked />
                <input type="radio" id="rad_1" checked />
                <div class="match__message match-warning">Some warning</div>
                <div class="match__status winner">Won</div>
                <div class="match-points tie-break" style="display: block;"></div>
                <input name="match_status_field" value="completed" />
                <div class="match-points__cell-input--won is-invalid input-validation-error"></div>
            </div>
        `;

        resetScoresInContainer('#testContainer');

        expect(document.getElementById('home_player_1').value).toBe('');
        expect(spyCalc).toHaveBeenCalled();
        expect(document.getElementById('score_1').value).toBe('');
        expect(document.getElementById('sel_1').value).toBe('0');
        expect(document.getElementById('chk_1').checked).toBe(false);
        expect(document.getElementById('rad_1').checked).toBe(false);

        const $msg = jQuery('.match__message');
        expect($msg.hasClass('match-warning')).toBe(false);
        expect($msg.hasClass('d-none')).toBe(true);
        expect($msg.html()).toBe('');

        const $status = jQuery('.match__status');
        expect($status.hasClass('winner')).toBe(false);
        expect($status.html()).toBe('');

        expect(jQuery('[name="match_status_field"]').val()).toBe('');
        expect(jQuery('.is-invalid').length).toBe(0);

        spyCalc.mockRestore();
    });

    test('resetMatchScoresByFormId calls resetScoresInContainer with #formId', () => {
        document.body.innerHTML = `
            <form id="myForm">
                <input type="text" id="field1" value="hello" />
            </form>
        `;
        resetMatchScoresByFormId('myForm');
        expect(document.getElementById('field1').value).toBe('');
    });

    test('initializeResetMatchScores delegates click on data-form-id and data-rubber-id', () => {
        document.body.innerHTML = `
            <button id="btnForm" data-action="reset-match-scores" data-form-id="testForm"></button>
            <form id="testForm">
                <input type="text" id="inputForm" value="val" />
            </form>

            <button id="btnRubber" data-action="reset-match-scores" data-rubber-id="10"></button>
            <div id="rubber-10">
                <input type="text" id="inputRubber" value="rubberVal" />
            </div>
        `;

        initializeResetMatchScores();

        document.getElementById('btnForm').click();
        expect(document.getElementById('inputForm').value).toBe('');

        document.getElementById('btnRubber').click();
        expect(document.getElementById('inputRubber').value).toBe('');
    });
});
