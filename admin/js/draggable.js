const matches = document.querySelectorAll('.final-match');
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
  ev.target.classList.add('drag-over');
}
function dragOver(ev) {
  ev.preventDefault();
  ev.target.classList.add('drag-over');
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
  ev.target.appendChild(draggable);
  draggable.classList.remove('hide');
  const inputId = targetId.replace('schedule','match');
  const input = document.getElementById(inputId);
  input.value=id.replace('match-','');
  const sourceinputId = sourceId.replace('schedule','match');
  if (sourceinputId != '') {
    const sourceinput = document.getElementById(sourceinputId);
    sourceinput.value='';
  }
}
const inputs = document.querySelectorAll('.matchId');
inputs.forEach(input => {
	if (input.value != '') {
		let match='match-' + input.value;
		let matchschedule=document.getElementById(input.parentElement.id);
		let draggable = document.getElementById(match);
		matchschedule.appendChild(draggable);
	}
});
