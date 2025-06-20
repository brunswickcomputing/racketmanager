jQuery(document).ready(function () {
    tabData();
    tabDataLinks();
});
jQuery(document).ajaxComplete(function () {
    tabData();
    tabDataLinks();
});
function tabData () {
//* Add event listener for tab data links
    const tabData = document.querySelectorAll('.tabData');
    tabData.forEach(function(el) {
           el.removeEventListener('click', tabDataClick);
           el.addEventListener('click', tabDataClick);
    });
}
function tabDataClick (e) {
    let type = this.dataset.type;
    let typeId = this.dataset.typeId;
    let season = this.dataset.season;
    let name = this.dataset.name;
    let competitionType = this.dataset.competitionType;
    Racketmanager.tabData(e,type,typeId,season,name,competitionType);
}
function tabDataLinks () {
//* Add event listener for tab data links
    const tabDataLinks = document.querySelectorAll('.tabDataLink');
    tabDataLinks.forEach(function(el) {
            el.removeEventListener('click', tabDataLinkClick);
            el.addEventListener('click', tabDataLinkClick);
    });
}
function tabDataLinkClick (e) {
    let type = this.dataset.type;
    let typeId = this.dataset.typeId;
    let season = this.dataset.season;
    let link = this.dataset.link;
    let linkId = this.dataset.linkId;
    let linkType = this.dataset.linkType;
    Racketmanager.tabDataLink(e,type,typeId,season,link,linkId,linkType);
}

