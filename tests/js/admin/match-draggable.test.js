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
});
