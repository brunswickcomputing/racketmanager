/**
 * Match drag-and-drop scheduling interactions for tournament finals / cups.
 * Uses native Drag and Drop APIs and standard DOM methods without jQuery.
 *
 * @module admin/features/draggable/match-draggable
 */

/**
 * Handle dragstart on match element.
 *
 * @param {DragEvent} ev
 */
export function dragStart(ev) {
    if (ev.dataTransfer) {
        ev.dataTransfer.setData('text/plain', ev.target.id);
    }
    setTimeout(() => {
        if (ev.target && ev.target.classList) {
            ev.target.classList.add('hide');
        }
    }, 0);
}

/**
 * Handle dragend on match element.
 *
 * @param {DragEvent} ev
 */
export function dragEnd(ev) {
    if (ev.target && ev.target.classList) {
        ev.target.classList.remove('hide');
    }
}

/**
 * Check if the box already contains a finals match element.
 *
 * @param {HTMLElement} box
 * @returns {boolean}
 */
function hasFinalsMatch(box) {
    return Boolean(box && typeof box.querySelector === 'function' && box.querySelector('.finals-match'));
}

/**
 * Handle dragenter on tournament match container.
 *
 * @param {DragEvent} ev
 */
export function dragEnter(ev) {
    ev.preventDefault();
    const box = ev.currentTarget || this;
    if (hasFinalsMatch(box)) {
        return;
    }
    if (ev.target && ev.target.classList) {
        ev.target.classList.add('drag-over');
    }
}

/**
 * Handle dragover on tournament match container.
 *
 * @param {DragEvent} ev
 */
export function dragOver(ev) {
    ev.preventDefault();
    dragEnter(ev);
}

/**
 * Handle dragleave on tournament match container.
 *
 * @param {DragEvent} ev
 */
export function dragLeave(ev) {
    if (ev.target && ev.target.classList) {
        ev.target.classList.remove('drag-over');
    }
}

/**
 * Handle drop on tournament match container.
 *
 * @param {DragEvent} ev
 */
export function drop(ev) {
    const target = ev.target;
    if (!target) return;
    const targetId = target.id || '';
    if (target.classList) {
        target.classList.remove('drag-over');
    }
    const id = ev.dataTransfer ? ev.dataTransfer.getData('text/plain') : '';
    if (!id) return;
    const draggable = document.getElementById(id);
    if (!draggable) return;
    const sourceParent = draggable.parentElement;
    const sourceId = sourceParent ? sourceParent.id : '';

    if (draggable.classList) {
        draggable.classList.remove('hide');
    }
    const box = ev.currentTarget || this;
    if (hasFinalsMatch(box)) {
        return;
    }
    target.appendChild(draggable);
    const inputId = targetId.replace('schedule', 'match');
    const input = document.getElementById(inputId);
    if (input) {
        input.value = id.replace('match-', '');
    }
    const sourceInputId = sourceId.replace('schedule', 'match');
    if (sourceInputId !== '') {
        const sourceInput = document.getElementById(sourceInputId);
        if (sourceInput) {
            sourceInput.value = '';
        }
    }
}

/**
 * Restore existing scheduled matches on page load based on .matchId inputs.
 *
 * @param {Document|HTMLElement} [root=document]
 */
export function restoreMatchSchedule(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') return;
    const inputs = root.querySelectorAll('.matchId');
    for (const input of inputs) {
        if (input.value !== '' && input.parentElement) {
            const match = 'match-' + input.value;
            const matchSchedule = document.getElementById(input.parentElement.id);
            const draggable = document.getElementById(match);
            if (draggable && matchSchedule) {
                matchSchedule.appendChild(draggable);
            }
        }
    }
}

/**
 * Initialize match drag-and-drop listeners and initial schedule state.
 *
 * @param {Document|HTMLElement} [root=document]
 */
export function initMatchDraggable(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') return;

    const matches = root.querySelectorAll('.finals-match');
    for (const match of matches) {
        match.addEventListener('dragstart', dragStart);
        match.addEventListener('dragend', dragEnd);
    }

    const boxes = root.querySelectorAll('.tournament-match');
    for (const box of boxes) {
        box.addEventListener('dragenter', dragEnter);
        box.addEventListener('dragover', dragOver);
        box.addEventListener('dragleave', dragLeave);
        box.addEventListener('drop', drop);
    }

    restoreMatchSchedule(root);
}
