/**
 * Tests for match drag-and-drop scheduler module.
 */
import {
  initMatchDraggable,
  dragStart,
  dragEnd,
  dragEnter,
  dragOver,
  dragLeave,
  drop,
  restoreMatchSchedule,
} from '../../../src/js/admin/features/draggable/match-draggable.js';

describe('Admin feature: match-draggable', () => {
  let container;

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  it('restores existing scheduled matches into their containers based on .matchId inputs', () => {
    container.innerHTML = `
      <div id="schedule-1" class="tournament-match">
        <input type="hidden" class="matchId" value="101">
      </div>
      <div id="unassigned">
        <div id="match-101" class="finals-match">Match 101</div>
      </div>
    `;

    restoreMatchSchedule(container);

    const scheduleBox = container.querySelector('#schedule-1');
    const matchEl = container.querySelector('#match-101');
    expect(scheduleBox.contains(matchEl)).toBe(true);
  });

  it('handles dragStart and sets transfer data and hide class', (done) => {
    const matchEl = document.createElement('div');
    matchEl.id = 'match-202';
    matchEl.className = 'finals-match';
    container.appendChild(matchEl);

    const setData = jest.fn();
    const ev = {
      target: matchEl,
      dataTransfer: { setData },
    };

    dragStart(ev);

    expect(setData).toHaveBeenCalledWith('text/plain', 'match-202');
    setTimeout(() => {
      expect(matchEl.classList.contains('hide')).toBe(true);
      done();
    }, 10);
  });

  it('handles dragEnd and removes hide class', () => {
    const matchEl = document.createElement('div');
    matchEl.id = 'match-202';
    matchEl.className = 'finals-match hide';
    container.appendChild(matchEl);

    dragEnd({ target: matchEl });

    expect(matchEl.classList.contains('hide')).toBe(false);
  });

  it('handles dragEnter and dragLeave for drag-over state', () => {
    const box = document.createElement('div');
    box.className = 'tournament-match';
    container.appendChild(box);

    const preventDefault = jest.fn();
    dragEnter.call(box, { preventDefault, target: box, currentTarget: box });
    expect(preventDefault).toHaveBeenCalled();
    expect(box.classList.contains('drag-over')).toBe(true);

    dragLeave({ target: box });
    expect(box.classList.contains('drag-over')).toBe(false);
  });

  it('delegates dragOver to dragEnter and prevents default', () => {
    const box = document.createElement('div');
    box.className = 'tournament-match';
    container.appendChild(box);

    const preventDefault = jest.fn();
    dragOver.call(box, { preventDefault, target: box, currentTarget: box });
    expect(preventDefault).toHaveBeenCalled();
    expect(box.classList.contains('drag-over')).toBe(true);
  });

  it('prevents dragEnter when box already has a finals match', () => {
    const box = document.createElement('div');
    box.className = 'tournament-match';
    const existing = document.createElement('div');
    existing.className = 'finals-match';
    box.appendChild(existing);
    container.appendChild(box);

    const preventDefault = jest.fn();
    dragEnter.call(box, { preventDefault, target: box, currentTarget: box });
    expect(box.classList.contains('drag-over')).toBe(false);
  });

  it('handles drop, moves element, and updates associated inputs', () => {
    container.innerHTML = `
      <div id="schedule-src" class="tournament-match">
        <input type="hidden" id="match-src" value="303">
        <div id="match-303" class="finals-match">Match 303</div>
      </div>
      <div id="schedule-dest" class="tournament-match">
        <input type="hidden" id="match-dest" value="">
      </div>
    `;

    const destBox = container.querySelector('#schedule-dest');
    const draggable = container.querySelector('#match-303');
    const getData = jest.fn().mockReturnValue('match-303');

    drop.call(destBox, {
      target: destBox,
      currentTarget: destBox,
      dataTransfer: { getData },
    });

    expect(destBox.contains(draggable)).toBe(true);
    expect(container.querySelector('#match-dest').value).toBe('303');
    expect(container.querySelector('#match-src').value).toBe('');
  });

  it('prevents drop when destination box already contains a finals match', () => {
    container.innerHTML = `
      <div id="schedule-dest" class="tournament-match">
        <div id="match-existing" class="finals-match">Existing</div>
      </div>
      <div id="schedule-src" class="tournament-match">
        <div id="match-303" class="finals-match">Match 303</div>
      </div>
    `;

    const destBox = container.querySelector('#schedule-dest');
    const draggable = container.querySelector('#match-303');
    const getData = jest.fn().mockReturnValue('match-303');

    drop.call(destBox, {
      target: destBox,
      currentTarget: destBox,
      dataTransfer: { getData },
    });

    expect(destBox.contains(draggable)).toBe(false);
  });

  it('initMatchDraggable attaches event listeners and restores schedule', () => {
    container.innerHTML = `
      <div id="schedule-1" class="tournament-match">
        <input type="hidden" class="matchId" value="404">
      </div>
      <div id="unassigned">
        <div id="match-404" class="finals-match">Match 404</div>
      </div>
    `;

    initMatchDraggable(container);

    const scheduleBox = container.querySelector('#schedule-1');
    const matchEl = container.querySelector('#match-404');
    expect(scheduleBox.contains(matchEl)).toBe(true);
  });

  it('handles edge cases in drop: missing target, empty dataTransfer, missing draggable element', () => {
    expect(() => drop({})).not.toThrow();

    const box = document.createElement('div');
    box.id = 'schedule-box';
    container.appendChild(box);

    // Empty dataTransfer
    expect(() => drop.call(box, { target: box, currentTarget: box, dataTransfer: null })).not.toThrow();

    // DataTransfer with non-existent id
    const getData = jest.fn().mockReturnValue('non-existent-id');
    expect(() => drop.call(box, { target: box, currentTarget: box, dataTransfer: { getData } })).not.toThrow();

    // Draggable element without parent id
    const orphan = document.createElement('div');
    orphan.id = 'orphan-match';
    document.body.appendChild(orphan);
    getData.mockReturnValue('orphan-match');
    expect(() => drop.call(box, { target: box, currentTarget: box, dataTransfer: { getData } })).not.toThrow();
    if (orphan.parentElement) {
      orphan.parentElement.removeChild(orphan);
    }
  });

  it('dragStart and dragEnd handle events when dataTransfer or target classList is missing', (done) => {
    expect(() => dragStart({ target: {} })).not.toThrow();
    expect(() => dragEnd({ target: {} })).not.toThrow();
    setTimeout(done, 10);
  });

  it('dragEnter and dragLeave handle events when target classList is missing', () => {
    const box = document.createElement('div');
    expect(() => dragEnter.call(box, { preventDefault: jest.fn(), target: {}, currentTarget: box })).not.toThrow();
    expect(() => dragLeave({ target: {} })).not.toThrow();
  });

  it('initMatchDraggable and restoreMatchSchedule handle invalid roots gracefully', () => {
    expect(() => initMatchDraggable(null)).not.toThrow();
    expect(() => initMatchDraggable({})).not.toThrow();
    expect(() => restoreMatchSchedule(null)).not.toThrow();
    expect(() => restoreMatchSchedule({})).not.toThrow();
  });

  it('dragEnter falls back to this when currentTarget is undefined', () => {
    const box = document.createElement('div');
    box.className = 'tournament-match';
    container.appendChild(box);

    const preventDefault = jest.fn();
    dragEnter.call(box, { preventDefault, target: box });
    expect(preventDefault).toHaveBeenCalled();
    expect(box.classList.contains('drag-over')).toBe(true);
  });

  it('dragEnter returns early when box has finals match via this fallback', () => {
    const box = document.createElement('div');
    box.className = 'tournament-match';
    const match = document.createElement('div');
    match.className = 'finals-match';
    box.appendChild(match);
    container.appendChild(box);

    const preventDefault = jest.fn();
    dragEnter.call(box, { preventDefault, target: box });
    expect(box.classList.contains('drag-over')).toBe(false);
  });

  it('dragLeave handles missing target or missing classList gracefully', () => {
    expect(() => dragLeave({})).not.toThrow();
    expect(() => dragLeave({ target: null })).not.toThrow();
  });

  it('drop handles missing currentTarget fallback to this, missing target id, and missing classList', () => {
    const destBox = document.createElement('div');
    destBox.className = 'tournament-match';
    container.appendChild(destBox);

    const matchEl = document.createElement('div');
    matchEl.id = 'match-505';
    // matchEl is an orphan with null parentElement
    const spy = jest.spyOn(document, 'getElementById').mockImplementation((id) => {
      if (id === 'match-505') return matchEl;
      return null;
    });

    const getData = jest.fn().mockReturnValue('match-505');

    // destBox has no id, currentTarget is undefined, matchEl has no classList property
    Object.defineProperty(matchEl, 'classList', { value: undefined, configurable: true });

    // target without classList
    const targetObj = {
      appendChild: jest.fn(),
    };

    try {
      drop.call(destBox, {
        target: targetObj,
        dataTransfer: { getData },
      });

      expect(targetObj.appendChild).toHaveBeenCalledWith(matchEl);
    } finally {
      spy.mockRestore();
    }
  });

  it('drop returns early when dataTransfer.getData returns empty string', () => {
    const destBox = document.createElement('div');
    destBox.id = 'schedule-dest-empty';
    destBox.className = 'tournament-match';
    container.appendChild(destBox);

    const getData = jest.fn().mockReturnValue('');

    drop.call(destBox, {
      target: destBox,
      currentTarget: destBox,
      dataTransfer: { getData },
    });

    expect(destBox.children.length).toBe(0);
  });

  it('drop handles missing input and missing sourceInput in DOM gracefully', () => {
    container.innerHTML = `
      <div id="schedule-src2" class="tournament-match">
        <div id="match-606" class="finals-match">Match 606</div>
      </div>
      <div id="schedule-dest2" class="tournament-match"></div>
    `;

    const destBox = container.querySelector('#schedule-dest2');
    const draggable = container.querySelector('#match-606');
    const getData = jest.fn().mockReturnValue('match-606');

    // Neither #match-dest2 nor #match-src2 exists in DOM
    drop.call(destBox, {
      target: destBox,
      currentTarget: destBox,
      dataTransfer: { getData },
    });

    expect(destBox.contains(draggable)).toBe(true);
  });

  it('drop returns early when box has finals match via this fallback', () => {
    const destBox = document.createElement('div');
    destBox.className = 'tournament-match';
    const existing = document.createElement('div');
    existing.className = 'finals-match';
    destBox.appendChild(existing);
    container.appendChild(destBox);

    const draggable = document.createElement('div');
    draggable.id = 'match-707';
    draggable.className = 'finals-match';
    container.appendChild(draggable);

    const getData = jest.fn().mockReturnValue('match-707');

    drop.call(destBox, {
      target: destBox,
      dataTransfer: { getData },
    });

    expect(destBox.contains(draggable)).toBe(false);
  });

  it('restoreMatchSchedule skips empty inputs, missing parent, missing draggable, or missing schedule', () => {
    container.innerHTML = `
      <div id="schedule-valid" class="tournament-match">
        <input type="hidden" class="matchId" value="801">
      </div>
      <div id="schedule-missing-draggable" class="tournament-match">
        <input type="hidden" class="matchId" value="802">
      </div>
      <div class="no-id-parent">
        <input type="hidden" class="matchId" value="803">
      </div>
      <div>
        <input type="hidden" class="matchId" value="">
      </div>
      <div id="unassigned">
        <div id="match-801" class="finals-match">Match 801</div>
        <div id="match-803" class="finals-match">Match 803</div>
      </div>
    `;

    // Also test input without parentElement
    const orphanInput = document.createElement('input');
    orphanInput.className = 'matchId';
    orphanInput.value = '804';
    const mockRoot = {
      querySelectorAll: () => [
        ...container.querySelectorAll('.matchId'),
        orphanInput,
      ],
    };

    restoreMatchSchedule(mockRoot);

    expect(container.querySelector('#schedule-valid').contains(container.querySelector('#match-801'))).toBe(true);
    expect(container.querySelector('#unassigned').contains(container.querySelector('#match-803'))).toBe(true);
    expect(container.querySelector('#schedule-missing-draggable').children.length).toBe(1);
    expect(container.querySelector('#schedule-missing-draggable').querySelector('.finals-match')).toBeNull();
    expect(container.querySelector('#match-804')).toBeNull();
  });

  it('restoreMatchSchedule and initMatchDraggable work with default root parameter (document)', () => {
    container.innerHTML = `
      <div id="schedule-doc" class="tournament-match">
        <input type="hidden" class="matchId" value="901">
      </div>
      <div id="unassigned-doc">
        <div id="match-901" class="finals-match">Match 901</div>
      </div>
    `;

    restoreMatchSchedule();
    expect(container.querySelector('#schedule-doc').contains(container.querySelector('#match-901'))).toBe(true);

    initMatchDraggable();
  });

  it('initMatchDraggable attaches operational drag and drop event listeners', () => {
    container.innerHTML = `
      <div id="box-events" class="tournament-match"></div>
      <div id="match-events" class="finals-match" draggable="true">Match</div>
    `;

    initMatchDraggable(container);

    const box = container.querySelector('#box-events');
    const match = container.querySelector('#match-events');

    // Trigger dragstart
    const setData = jest.fn();
    const dragStartEv = new Event('dragstart', { bubbles: true });
    dragStartEv.dataTransfer = { setData };
    match.dispatchEvent(dragStartEv);
    expect(setData).toHaveBeenCalledWith('text/plain', 'match-events');

    // Trigger dragend
    match.dispatchEvent(new Event('dragend', { bubbles: true }));

    // Trigger dragenter, dragover, dragleave
    box.dispatchEvent(new Event('dragenter', { bubbles: true }));
    expect(box.classList.contains('drag-over')).toBe(true);

    box.dispatchEvent(new Event('dragover', { bubbles: true }));
    expect(box.classList.contains('drag-over')).toBe(true);

    box.dispatchEvent(new Event('dragleave', { bubbles: true }));
    expect(box.classList.contains('drag-over')).toBe(false);
  });
});
