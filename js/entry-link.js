document.getElementById('entrySubmit').addEventListener('click', function (e) {
    let type = this.dataset.type;
    Racketmanager.entryRequest(e, type);
});