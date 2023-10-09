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

function fileDownload( fileURL, fileName, fileSize) {

    const fileSizeString = formatFileSize(fileSize);
    const fileType = fileURL.substring(fileURL.lastIndexOf('.')+1);
    const actualFileName = fileURL.substr(fileURL.lastIndexOf('/')+1,fileURL.lastIndexOf('.')-1 - fileURL.lastIndexOf('/'));

    pushToDataLayer({
        "event":        'file_download',
        "link_text": fileName,
        "link_url": fileURL,
        "file_extension": fileType,
        "file_size": fileSizeString,
        "file_name": actualFileName
    });

}

function pushToDataLayer(array){
    console.log("dada");
    array = (typeof array === 'string')? JSON.parse(array) : array;

    window.dataLayer.push(array);
}

function formatFileSize(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}