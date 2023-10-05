// this is ran on all the pages and assign print_page() to the print button
window.onload = function() {
    const printLink = document.querySelector('.app-c-print-link__link');

    if (printLink != null) {
        printLink.addEventListener('click', () => {
            pushToDataLayer({
                "event":        'print_page',
                "link_text":    printLink.textContent.trim()
            });
        });
    }
}

//this is only ran on show framework and show supplier page
function frameworkAndSupplierPage() {
    const externalLinks = Array.from(document.querySelectorAll('main a')).filter(link => !(link.href.includes('crowncommercial') || link.href.includes('tel')) );

    externalLinks.forEach(link => {
        link.addEventListener('click', () => {
            pushToDataLayer({
                "event":        'portal_click',
                "link_text":    link.textContent.trim(),
                "link_url":     link.href
            });
        });
    });
}

function pushToDataLayer(array){
    array = (typeof array === 'string')? JSON.parse(array) : array;

    window.dataLayer.push(array);
}