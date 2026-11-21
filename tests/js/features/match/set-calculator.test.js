import {
  initializeSetCalculator,
  SetCalculator,
  SetCalculatorTieBreak,
} from '../../../../src/js/features/match/set-calculator';

describe('Set Calculator feature', () => {
  let container;

  beforeAll(() => {
    initializeSetCalculator();
  });

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  const setupSetDom = (setRef = 'set_0_0') => {
    const wrapper = document.createElement('div');
    wrapper.id = setRef;
    wrapper.setAttribute('data-maxwin', '7');
    wrapper.setAttribute('data-minwin', '6');
    wrapper.setAttribute('data-maxloss', '5');
    wrapper.setAttribute('data-minloss', '4');
    wrapper.setAttribute('data-tiebreakset', '6');

    wrapper.innerHTML = `
      <input type="number" id="${setRef}_player1" class="points" data-action="set-calculator" value="" />
      <input type="number" id="${setRef}_player2" class="points" data-action="set-calculator" value="" />
      <div id="${setRef}_tiebreak_wrapper" style="display:none;">
        <input type="number" id="${setRef}_tiebreak" class="points" data-action="set-calculator-tiebreak" value="" />
      </div>
    `;
    container.appendChild(wrapper);

    return {
      player1: wrapper.querySelector(`#${setRef}_player1`),
      player2: wrapper.querySelector(`#${setRef}_player2`),
      tiebreak: wrapper.querySelector(`#${setRef}_tiebreak`),
      tiebreakWrapper: wrapper.querySelector(`#${setRef}_tiebreak_wrapper`),
    };
  };

  describe('delegated focusout listener for [data-action="set-calculator"]', () => {
    it('calculates alt score when opponent input is empty and applies winning class', () => {
      const { player1, player2 } = setupSetDom('set_0_0');

      player1.value = '4'; // loser score entered
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      // CalculateAltScore calculates minWin (6) for player2
      expect(player2.value).toBe('6');
      expect(player2.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(player1.classList.contains('input-validation-error')).toBe(false);
    });

    it('assigns won class to winner and validates winning score', () => {
      const { player1, player2 } = setupSetDom('set_0_1');

      player1.value = '6';
      player2.value = '3';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(player1.classList.contains('input-validation-error')).toBe(false);
    });

    it('validates invalid scores on focusout', () => {
      const { player1, player2 } = setupSetDom('set_0_2');

      player1.value = '9'; // exceeds maxWin (7)
      player2.value = '5';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('input-validation-error')).toBe(true);
    });

    it('executes exactly once per focusout event and ignores capturing blur', () => {
      const { player1, player2 } = setupSetDom('set_0_3');
      player1.value = '6';
      player2.value = '2';

      const addClassSpy = jest.spyOn(jQuery.fn, 'addClass');
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      // won class should have been added once during this single focusout
      const wonCalls = addClassSpy.mock.calls.filter((call) => call[0] === 'match-points__cell-input--won');
      expect(wonCalls.length).toBe(1);

      // Verify that blur does not double-execute now that only focusout is listened to
      addClassSpy.mockClear();
      player1.dispatchEvent(new Event('blur', { bubbles: false }));
      const blurWonCalls = addClassSpy.mock.calls.filter((call) => call[0] === 'match-points__cell-input--won');
      expect(blurWonCalls.length).toBe(0);

      addClassSpy.mockRestore();
    });
  });

  describe('delegated focusout listener for [data-action="set-calculator-tiebreak"]', () => {
    it('removes input-validation-error on valid tiebreak score', () => {
      const { tiebreak } = setupSetDom('set_1_0');
      tiebreak.classList.add('input-validation-error');
      tiebreak.value = '7';

      tiebreak.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(tiebreak.classList.contains('input-validation-error')).toBe(false);
    });

    it('adds input-validation-error when tiebreak score is invalid/NaN', () => {
      const { tiebreak } = setupSetDom('set_1_1');
      tiebreak.value = 'not-a-number';

      tiebreak.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(tiebreak.classList.contains('input-validation-error')).toBe(true);
    });
  });

  describe('dynamically inserted inputs', () => {
    it('handles focusout on inputs inserted into the DOM after initialization', () => {
      // Inputs inserted dynamically
      const dynamicDom = setupSetDom('set_9_9');
      dynamicDom.player1.value = '6';
      dynamicDom.player2.value = '4';

      dynamicDom.player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(dynamicDom.player1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(dynamicDom.player1.classList.contains('input-validation-error')).toBe(false);
    });
  });

  describe('graceful handling of missing and malformed inputs', () => {
    it('does not throw when target element has no id', () => {
      const malformedInput = document.createElement('input');
      malformedInput.setAttribute('data-action', 'set-calculator');
      container.appendChild(malformedInput);

      expect(() => {
        malformedInput.dispatchEvent(new Event('focusout', { bubbles: true }));
      }).not.toThrow();
    });

    it('does not throw when alternate player input does not exist in DOM', () => {
      const orphanedInput = document.createElement('input');
      orphanedInput.id = 'set_0_0_player1';
      orphanedInput.setAttribute('data-action', 'set-calculator');
      orphanedInput.value = '6';
      container.appendChild(orphanedInput);

      expect(() => {
        orphanedInput.dispatchEvent(new Event('focusout', { bubbles: true }));
      }).not.toThrow();
    });

    it('does not throw when tiebreak input has no id or is malformed', () => {
      const malformedTb = document.createElement('input');
      malformedTb.setAttribute('data-action', 'set-calculator-tiebreak');
      container.appendChild(malformedTb);

      expect(() => {
        malformedTb.dispatchEvent(new Event('focusout', { bubbles: true }));
      }).not.toThrow();
    });

    it('does not throw when SetCalculator or SetCalculatorTieBreak are directly invoked with null/undefined', () => {
      expect(() => SetCalculator(null)).not.toThrow();
      expect(() => SetCalculator(undefined)).not.toThrow();
      expect(() => SetCalculatorTieBreak(null)).not.toThrow();
      expect(() => SetCalculatorTieBreak(undefined)).not.toThrow();
    });

    it('does not throw when SetCalculator throws an unexpected error on focusout', () => {
      const { player1 } = setupSetDom('set_error_test');
      player1.value = '6';
      const origGetAttribute = player1.getAttribute;
      player1.getAttribute = () => { throw new Error('Unexpected attribute error'); };
      try {
        expect(() => {
          player1.dispatchEvent(new Event('focusout', { bubbles: true }));
        }).not.toThrow();
      } finally {
        player1.getAttribute = origGetAttribute;
      }
    });
  });

  describe('tiebreak UI management and visibility syncing', () => {
    it('shows tiebreak wrapper and focuses tiebreak input when player 2 input reaches tiebreak condition (7-6)', () => {
      const { player1, player2, tiebreak, tiebreakWrapper } = setupSetDom('set_2_0');
      const focusSpy = jest.spyOn(tiebreak, 'focus');

      player1.value = '6';
      player2.value = '7';
      player2.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(tiebreakWrapper.style.display).not.toBe('none');
      expect(focusSpy).toHaveBeenCalled();
      focusSpy.mockRestore();
    });

    it('shows tiebreak wrapper and focuses tiebreak input when player 2 input completes 6-7 tiebreak', () => {
      const { player1, player2, tiebreak, tiebreakWrapper } = setupSetDom('set_2_1');
      const focusSpy = jest.spyOn(tiebreak, 'focus');

      player1.value = '7';
      player2.value = '6';
      player2.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(tiebreakWrapper.style.display).not.toBe('none');
      expect(focusSpy).toHaveBeenCalled();
      focusSpy.mockRestore();
    });

    it('hides tiebreak wrapper and resets tiebreak score when player 2 input does not reach tiebreak (6-4)', () => {
      const { player1, player2, tiebreak, tiebreakWrapper } = setupSetDom('set_2_2');
      tiebreakWrapper.style.display = 'block';
      tiebreak.value = '7';

      player1.value = '6';
      player2.value = '4';
      player2.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(tiebreakWrapper.style.display).toBe('none');
      expect(tiebreak.value).toBe('');
    });

    it('does not alter tiebreak visibility when player 1 input is blurred', () => {
      const { player1, player2, tiebreakWrapper } = setupSetDom('set_2_3');
      tiebreakWrapper.style.display = 'none';

      player1.value = '7';
      player2.value = '6';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      // Only player 2 blur triggers tiebreak visibility sync in legacy logic
      expect(tiebreakWrapper.style.display).toBe('none');
    });
  });

  describe('tennis score validation rules and edge cases', () => {
    it('marks both player inputs with input-validation-error when scores are equal (5-5)', () => {
      const { player1, player2 } = setupSetDom('set_3_0');

      player1.value = '5';
      player2.value = '5';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('input-validation-error')).toBe(true);
      expect(player2.classList.contains('input-validation-error')).toBe(true);
      expect(player1.classList.contains('match-points__cell-input--won')).toBe(false);
      expect(player2.classList.contains('match-points__cell-input--won')).toBe(false);
    });

    it('marks both inputs with input-validation-error on invalid minWin score (6-5 when maxWin is 7)', () => {
      const { player1, player2 } = setupSetDom('set_3_1');

      player1.value = '6';
      player2.value = '5';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('input-validation-error')).toBe(true);
      expect(player2.classList.contains('input-validation-error')).toBe(true);
    });

    it('marks both inputs with input-validation-error on invalid maxWin score (7-3 when maxLoss is 5)', () => {
      const { player1, player2 } = setupSetDom('set_3_2');

      player1.value = '7';
      player2.value = '3';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('input-validation-error')).toBe(true);
      expect(player2.classList.contains('input-validation-error')).toBe(true);
    });

    it('marks winning input with won class at 7-6 without flagging an empty tiebreak input', () => {
      const { player1, player2, tiebreak } = setupSetDom('set_3_3');
      player1.value = '7';
      player2.value = '6';
      player2.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(player1.classList.contains('input-validation-error')).toBe(false);
      expect(tiebreak.classList.contains('input-validation-error')).toBe(false);
    });

    it('marks winning input with won class when maxWin is valid (7-5)', () => {
      const { player1, player2 } = setupSetDom('set_3_4');

      player1.value = '7';
      player2.value = '5';
      player1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(player1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(player1.classList.contains('input-validation-error')).toBe(false);
      expect(player2.classList.contains('input-validation-error')).toBe(false);
    });

    it('validates advantage sets where maxWin is larger than 7 (8-6 valid, 8-5 invalid)', () => {
      const wrapper = document.createElement('div');
      wrapper.id = 'set_4_0';
      wrapper.setAttribute('data-maxwin', '12');
      wrapper.setAttribute('data-minwin', '6');
      wrapper.setAttribute('data-maxloss', '5');
      wrapper.setAttribute('data-minloss', '4');
      wrapper.setAttribute('data-tiebreakset', '12');
      wrapper.innerHTML = `
        <input type="number" id="set_4_0_player1" class="points" data-action="set-calculator" value="" />
        <input type="number" id="set_4_0_player2" class="points" data-action="set-calculator" value="" />
      `;
      container.appendChild(wrapper);

      const p1 = wrapper.querySelector('#set_4_0_player1');
      const p2 = wrapper.querySelector('#set_4_0_player2');

      // 8-5: invalid advantage (not 2-game margin)
      p1.value = '8';
      p2.value = '5';
      p1.dispatchEvent(new Event('focusout', { bubbles: true }));
      expect(p1.classList.contains('input-validation-error')).toBe(true);

      // 8-3: invalid advantage (team2 < minLoss)
      p1.value = '8';
      p2.value = '3';
      p1.dispatchEvent(new Event('focusout', { bubbles: true }));
      expect(p1.classList.contains('input-validation-error')).toBe(true);

      // 8-6: valid advantage win
      p1.value = '8';
      p2.value = '6';
      p1.dispatchEvent(new Event('focusout', { bubbles: true }));
      expect(p1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(p1.classList.contains('input-validation-error')).toBe(false);
    });

    it('supports 3-part set reference IDs (set_0_player1 with setLength 5)', () => {
      const wrapper = document.createElement('div');
      wrapper.id = 'set_0';
      wrapper.setAttribute('data-maxwin', '7');
      wrapper.setAttribute('data-minwin', '6');
      wrapper.setAttribute('data-maxloss', '5');
      wrapper.setAttribute('data-minloss', '4');
      wrapper.setAttribute('data-tiebreakset', '6');
      wrapper.innerHTML = `
        <input type="number" id="set_0_player1" class="points" data-action="set-calculator" value="" />
        <input type="number" id="set_0_player2" class="points" data-action="set-calculator" value="" />
      `;
      container.appendChild(wrapper);

      const p1 = wrapper.querySelector('#set_0_player1');
      const p2 = wrapper.querySelector('#set_0_player2');

      p1.value = '6';
      p2.value = '3';
      p1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(p1.classList.contains('match-points__cell-input--won')).toBe(true);
    });
  });

  describe('boundary score rules from public API', () => {
    it('does not flag 6-5 when maxWin equals minWin and marks the winner as won', () => {
      const wrapper = document.createElement('div');
      wrapper.id = 'set_5_0';
      wrapper.setAttribute('data-maxwin', '6');
      wrapper.setAttribute('data-minwin', '6');
      wrapper.setAttribute('data-maxloss', '5');
      wrapper.setAttribute('data-minloss', '4');
      wrapper.setAttribute('data-tiebreakset', '6');
      wrapper.innerHTML = `
        <input type="number" id="set_5_0_player1" class="points" data-action="set-calculator" value="" />
        <input type="number" id="set_5_0_player2" class="points" data-action="set-calculator" value="" />
      `;
      container.appendChild(wrapper);

      const p1 = wrapper.querySelector('#set_5_0_player1');
      const p2 = wrapper.querySelector('#set_5_0_player2');

      p1.value = '6';
      p2.value = '5';
      p1.dispatchEvent(new Event('focusout', { bubbles: true }));

      expect(p1.classList.contains('match-points__cell-input--won')).toBe(true);
      expect(p1.classList.contains('input-validation-error')).toBe(false);
      expect(p2.classList.contains('input-validation-error')).toBe(false);
    });
  });
});
