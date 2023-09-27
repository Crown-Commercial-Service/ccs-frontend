// this is ran on all the pages and assign print_page() to the print button
window.onload = function() {
    const printLink = document.querySelector('.app-c-print-link__link');

    if (printLink != null) {
        printLink.addEventListener('click', () => {
            pushToDataLayer({
                "event":        'print_page',
                "link_text":    printLink.textContent.trim(),
                "page_URL":     window.location.href
            });
        });
    }
}

function pushToDataLayer(array){
    array = (typeof array === 'string')? JSON.parse(array) : array;

    window.dataLayer.push(array);
}