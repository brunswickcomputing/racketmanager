const matches = document.querySelectorAll('.finals-match');
matches.forEach(match => {
  match.addEventListener('dragstart', dragStart);
  match.addEventListener('dragend', dragEnd);
});

function dragStart(ev) {
  ev.dataTransfer.setData("text/plain", ev.target.id);
  setTimeout(() => {
    ev.target.classList.add('hide');
  }, 0);
}

const boxes = document.querySelectorAll('.tournament-match');

boxes.forEach(box => {
  box.addEventListener('dragenter', dragEnter);
  box.addEventListener('dragover', dragOver);
  box.addEventListener('dragleave', dragLeave);
  box.addEventListener('drop', drop);
});
function dragEnd(ev) {
  ev.target.classList.remove('hide');
}
function dragEnter(ev) {
  ev.preventDefault();
  if (jQuery(this).children('.finals-match').length) {
    return;
  }
  ev.target.classList.add('drag-over');
}
function dragOver(ev) {
    ev.preventDefault();
    dragEnter(ev);
}
function dragLeave(ev) {
  ev.target.classList.remove('drag-over');
}
function drop(ev) {
  const targetId = ev.target.id;
  ev.target.classList.remove('drag-over');
  const id = ev.dataTransfer.getData("text/plain");
  const draggable = document.getElementById(id);
  const sourceId = draggable.parentElement.id;
  draggable.classList.remove('hide');
  if (jQuery(this).children('.finals-match').length) {
    return;
  }
  ev.target.appendChild(draggable);
  const inputId = targetId.replace('schedule','match');
  const input = document.getElementById(inputId);
  input.value=id.replace('match-','');
  const sourceInputId = sourceId.replace('schedule','match');
  if (sourceInputId !== '') {
    const sourceInput = document.getElementById(sourceInputId);
    sourceInput.value='';
  }
}
const inputs = document.querySelectorAll('.matchId');
inputs.forEach(input => {
    if (input.value !== '') {
        let match='match-' + input.value;
        let matchSchedule=document.getElementById(input.parentElement.id);
        let draggable = document.getElementById(match);
        if (draggable) {
            matchSchedule.appendChild(draggable);
        }
    }
});
